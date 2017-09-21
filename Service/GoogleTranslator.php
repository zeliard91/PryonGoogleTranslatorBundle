<?php

namespace Pryon\GoogleTranslatorBundle\Service;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GoogleTranslator
{
    // Base URL
    const URL = 'https://translation.googleapis.com/language/translate/v2';

    // Limit query by POST
    const POST_QUERY_LIMIT_SIZE = 5000;

    // POST init size with all required params
    const POST_QUERY_INIT_SIZE = 66;

    // POST init size with all required params
    const NB_MAX_SEGMENTS = 128;

    /**
     * Google API Key.
     *
     * @var string
     */
    private $apiKey;

    /**
     * Guzzle Client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * Cache Provider.
     *
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cacheProvider;

    /**
     * API methods to cache.
     *
     * @var array
     */
    private $cacheCalls;

    /**
     * @var null|array
     */
    private $supportedLanguages = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param string                      $apiKey Google API Key
     * @param \GuzzleHttp\ClientInterface $client Guzzle Client
     * @param \Psr\Log\LoggerInterface    $logger  Logger
     */
    public function __construct($apiKey, ClientInterface $client, LoggerInterface $logger = null)
    {
        $this->apiKey = $apiKey;
        $this->client = $client;
        $this->logger = (null === $logger) ? new NullLogger() : $logger;
    }

    /**
     * Define cache provider.
     *
     * @param CacheProvider $cacheProvider
     * @param array         $cacheCalls
     */
    public function setCache(CacheProvider $cacheProvider, array $cacheCalls)
    {
        $this->cacheProvider = $cacheProvider;
        $this->cacheProvider->setNamespace('pryon_translator');
        $this->cacheCalls = $cacheCalls;
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
        if (!isset($this->cacheCalls[$method]) || $this->cacheCalls[$method] !== true) {
            return null;
        }
        $id = $this->getCacheId($method, $params);

        return ($this->cacheProvider->contains($id)) ? $this->cacheProvider->fetch($id) : null;
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
     *
     * @return bool
     */
    protected function setCacheValue($method, $value, $params = array())
    {
        if (!isset($this->cacheCalls[$method]) || $this->cacheCalls[$method] !== true) {
            return false;
        }

        return $this->cacheProvider->save($this->getCacheId($method, $params), $value);
    }

    /**
     * Call API with curl.
     *
     * @param string $function
     * @param array $params
     * @param string $method
     *
     * @return mixed array or string
     *
     * @throws \Exception
     */
    private function call($function, $params = array(), $method = 'GET')
    {
        $params['key'] = $this->apiKey;

        $clientParams = [];

        if ('GET' === $method) {
            $clientParams['query'] = $params;
        } else {
            $clientParams['body'] = \GuzzleHttp\Psr7\build_query($params);
            $clientParams['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        try {
            $response = $this->client->request($method, self::URL.$function, $clientParams);
        } catch (RequestException $e) {
            $this->logger->error('REST Exception', ['e' => $e, 'url' => self::URL.$function, 'params' => $clientParams]);
            $message = $e->getResponse()->getReasonPhrase();

            $json = json_decode($e->getResponse()->getBody());
            if (null !== $json && isset($json->error, $json->error->message)) {
                $message = $json->error->message;
            }

            throw new \Exception($message, $e->getCode());
        }

        return $this->handlingCallResponse($response);
    }

    /**
     * Handling Guzzle Response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return mixed array or string
     *
     * @throws \Exception
     */
    private function handlingCallResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getReasonPhrase());
        }

        $json = json_decode($response->getBody());

        if (is_null($json)) {
            $this->logger->error('Unable to decode response : '.$response->getBody());
            throw new \Exception('Unable to decode response : '.$response->getBody());
        }
        if (!isset($json->data)) {
            if (isset($json->error, $json->error->message)) {
                throw new \Exception(
                    'Translator Error ('.$json->error->code.') : '.$json->error->message
                );
            }
            $this->logger->error('Unable to find data in response', ['response' => $response]);
            throw new \Exception('Unable to find data in response');
        }

        return $json->data;
    }

    /**
     * Return supported languages.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getSupportedLanguages()
    {
        $this->supportedLanguages = $this->getCacheValue('languages');
        if (is_null($this->supportedLanguages)) {
            $response = $this->call('/languages');
            if (!isset($response->languages)) {
                throw new \Exception('Unable to find languages');
            }
            $languages = array();
            foreach ($response->languages as $language) {
                $languages[] = $language->language;
            }
            $this->supportedLanguages = $languages;
            $this->setCacheValue('languages', $this->supportedLanguages);
        }

        return $this->supportedLanguages;
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
     *
     * @throws \Exception
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
