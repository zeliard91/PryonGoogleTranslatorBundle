<?php

namespace Pryon\GoogleTranslatorBundle\Service;

use Doctrine\Common\Cache\CacheProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GoogleTranslator
{
    // Google API Key
    private $api_key;

    /**
     * Cache Provider.
     *
     * @var Doctrine\Common\Cache\CacheProvider
     */
    private $cache_provider;

    /**
     * API methods to cache.
     *
     * @var array
     */
    private $cache_calls;

    // Base URL
    const URL = 'https://www.googleapis.com/language/translate/v2';

    // Limit query by POST
    const POST_QUERY_LIMIT_SIZE = 5000;

    // POST init size with all required params
    const POST_QUERY_INIT_SIZE = 66;

    // POST init size with all required params
    const NB_MAX_SEGMENTS = 128;

    private $supported_languages = null;

    /**
     * @var Symfony\Bridge\Monolog\LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param string          $api_key Google API Key
     * @param LoggerInterface $logger  Logger
     */
    public function __construct($api_key, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->api_key = $api_key;

        if (null === $logger) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * Define cache provider.
     *
     * @param CacheProvider $cache_provider
     * @param array         $cache_calls
     */
    public function setCache(CacheProvider $cache_provider, array $cache_calls)
    {
        $this->cache_provider = $cache_provider;
        $this->cache_provider->setNamespace('pryon_translator');
        $this->cache_calls = $cache_calls;
    }

    /**
     * Return value store in cache for method and params.
     *
     * @param string $method [description]
     * @param array  $params [description]
     *
     * @return mixed (null if method is not cached or value is not in cache)
     */
    protected function getCacheValue($method, $params = array())
    {
        if (!isset($this->cache_calls[$method]) || $this->cache_calls[$method] !== true) {
            return;
        }
        $id = $this->getCacheId($method, $params);

        return ($this->cache_provider->contains($id)) ? $this->cache_provider->fetch($id) : null;
    }

    /**
     * Get object cache id for method + params.
     *
     * @param string $method
     * @param array  $params
     *
     * @return string
     */
    protected function getCacheId($method, $params = array())
    {
        return $method.'_'.md5(http_build_query($params));
    }

    /**
     * save value in cache.
     *
     * @param string $method
     * @param mixed  $value
     * @param array  $params
     */
    protected function setCacheValue($method, $value, $params = array())
    {
        if (!isset($this->cache_calls[$method]) || $this->cache_calls[$method] !== true) {
            return;
        }

        return $this->cache_provider->save($this->getCacheId($method, $params), $value);
    }

    /**
     * Call API with curl.
     *
     * @param [type] $fonction [description]
     *
     * @return [type] [description]
     */
    private function call($fonction, $params = array(), $method = 'GET')
    {
        // Init cURL resource
        $ch = curl_init();
        if ($ch === false) {
            throw new \Exception('Unable to init curl session');
        }

        $url = self::URL.$fonction;
        $params['key'] = $this->api_key;

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
            if (isset($params['q'])) {
                // All the texts must be sent as the query 'q'
                $query_params = http_build_query($params['q'], 'q_');
                $query_params = preg_replace('/q\_[0-9]+\=/', 'q=', $query_params);

                unset($params['q']);
                $post_fields = http_build_query($params).'&'.$query_params;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
                $this->logger->debug('call POST '.$url.' with params : '.$post_fields);
            }
        } else {
            $url .= '?'.http_build_query($params);
            $this->logger->debug('call GET '.$url);
        }

        // cURL configuration
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Get response
        $response = curl_exec($ch);
        // Closing cURL session
        curl_close($ch);

        return $this->handlingCallResponse($response);
    }

    /**
     * Handling curl response.
     *
     * @param mixed $response curl response
     *
     * @return mixed array or string
     */
    private function handlingCallResponse($response)
    {
        if ($response === false) {
            throw new \Exception('Unable to get curl content');
        }
        // Decoding responses
        $json_response = json_decode($response);
        if (is_null($json_response)) {
            $this->logger->error('Unable to decode response : '.$response);
            throw new \Exception('Unable to decode response : '.$response);
        }
        if (!isset($json_response->data)) {
            if (isset($json_response->error, $json_response->error->message)) {
                throw new \Exception(
                    'Translator Error ('.$json_response->error->code.') : '.$json_response->error->message
                );
            }
            $this->logger->error('Unable to find data in response : '.print_r($response, true));
            throw new \Exception('Unable to find data in response');
        }

        return $json_response->data;
    }

    /**
     * Return supported languages.
     *
     * @return array
     */
    public function getSupportedLanguages()
    {
        $this->supported_languages = $this->getCacheValue('languages');
        if (is_null($this->supported_languages)) {
            $response = $this->call('/languages');
            if (!isset($response->languages)) {
                throw new \Exception('Unable to find languages');
            }
            $languages = array();
            foreach ($response->languages as $language) {
                $languages[] = $language->language;
            }
            $this->supported_languages = $languages;
            $this->setCacheValue('languages', $this->supported_languages);
        }

        return $this->supported_languages;
    }

    /**
     * Return translation of text.
     *
     * @param string       $source source language
     * @param string       $target destination
     * @param string|array $text   Text to translate
     *
     * @return string
     */
    public function translate($source, $target, $text)
    {
        if (!is_array($text)) {
            $results = $this->handleTranslateResponse($source, $target, $text);
        } else {
            $results = array();
            $size = self::POST_QUERY_INIT_SIZE;
            $nb_text = count($text);
            $texts = array();
            for ($i = 0; $i < $nb_text; ++$i) {
                $size += mb_strlen(urlencode($text[$i])) + 3;
                if ($size > self::POST_QUERY_LIMIT_SIZE || count($texts) == self::NB_MAX_SEGMENTS) {
                    // Get response
                    $results = array_merge($results, $this->handleTranslateResponse($source, $target, $texts, 'POST'));

                    $texts = array();
                    $size = self::POST_QUERY_INIT_SIZE;
                }
                $texts[] = $text[$i];
            }
            if ($size > self::POST_QUERY_INIT_SIZE) {
                $results = array_merge($results, $this->handleTranslateResponse($source, $target, $texts, 'POST'));
            }
        }

        return (count($results) == 1) ? $results[0] : $results;
    }

    /**
     * Send and receive translate query.
     *
     * @param string       $source source lang
     * @param string       $target destination lang
     * @param string|array $text   Text to translate
     * @param string       $method GET|POST
     *
     * @return array
     */
    private function handleTranslateResponse($source, $target, $text, $method = 'GET')
    {
        $results = $this->getCacheValue('translate', array($source, $target, $text, $method));
        if (is_null($results)) {
            $response = $this->call(
                '',
                array(
                    'source' => $source,
                    'target' => $target,
                    'q' => $text,
                ),
                $method
            );
            if (!isset($response->translations)) {
                throw new \Exception('Unable to find translations');
            }
            $results = array();
            foreach ($response->translations as $translations) {
                $results[] = $translations->translatedText;
            }
            $this->setCacheValue('translate', $results, array($source, $target, $text, $method));
        }

        return $results;
    }
}
