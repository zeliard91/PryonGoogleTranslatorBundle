<?php

namespace Pryon\GoogleTranslatorBundle\Service;

class GoogleTranslator
{
    
    // Google API Key
    private $api_key;

    // Base URL
    const url = 'https://www.googleapis.com/language/translate/v2';

    // Limit query by POST
    const post_query_limit_size = 5000;

    // POST init size with all required params
    const post_query_init_size = 66;

    // POST init size with all required params
    const nb_max_segments = 128;

    private $supported_languages = null;

    /**
     * Constructor
     * @param string $api_key [description]
     */
    public function __construct($api_key)
    {
        $this -> api_key = $api_key;
    }

    /**
     * Call API with curl
     * @param  [type] $fonction [description]
     * @return [type]           [description]
     */
    private function call($fonction, $params = array(), $method = 'GET')
    {
        // Init cURL resource
        $ch = curl_init();
        if ($ch === false)
        {
            throw new \Exception("Unable to init curl session");
        }
        
        $url = self::url . $fonction;
        $params['key'] = $this -> api_key;
        
        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, true); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET') );
            if (isset($params['q']))
            {
                // All the texts must be sent as the query 'q'
                $query_params = http_build_query($params['q'], 'q_');
                $query_params = preg_replace('/q\_[0-9]+\=/','q=', $query_params);
                
                unset($params['q']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params).'&'.$query_params);
            }
        }
        else
        {
            $url .= '?'.http_build_query($params);
        }
        
        // cURL configuration
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        // Get responses
        $response = curl_exec($ch);
        if ($response === false)
        {
            throw new \Exception("Unable to get curl content");
        }

        // Closing cURL session
        curl_close($ch);

        // Decoding responses
        $json_response = json_decode($response);
        if (is_null($json_response))
        {
            throw new \Exception("Unable to decode response : ".$response);
        }
        if (!isset($json_response -> data))
        {
            if (isset($json_response -> error, $json_response -> error -> message))
            {
                throw new \Exception('Translator Error ('.$json_response -> error -> code.') : '.$json_response -> error -> message);
            }
            throw new \Exception("Unable to find data in response");
        }
        
        return $json_response -> data;
    }
    
    /**
     * Return supported languages
     * @return array
     */
    public function getSupportedLanguages()
    {
        if (is_null($this -> supported_languages))
        {
            $response = $this -> call('/languages');
            if (!isset($response -> languages))
            {
                throw new \Exception("Unable to find languages");
            }
            $languages = array();
            foreach ($response -> languages as $language)
            {
                $languages[] = $language -> language;
            }
            $this -> supported_languages = $languages;
        }
        return $this -> supported_languages;
    }

    /**
     * Return translation of text
     * @param  string $source source language
     * @param  string $target destination
     * @param  string|array $text   Text to translate
     * @return string
     */
    public function translate($source, $target, $text)
    {
        if (!is_array($text))
        {
            $results = $this -> handleTranslateResponse($source, $target, $text);
        }
        else
        {
            $results = array();
            $size    = self::post_query_init_size;
            $nb_text = count($text);
            $texts = array();
            for ($i = 0 ; $i < $nb_text ; $i++)
            {
                $size += mb_strlen(urlencode($text[$i]))+3;
                if ($size > self::post_query_limit_size || count($texts) == self::nb_max_segments)
                {
                    // Get response
                    $results = array_merge($results, $this -> handleTranslateResponse($source, $target, $texts, 'POST'));
                   
                    $texts = array();
                    $size  = self::post_query_init_size;
                }
                $texts[] = $text[$i];
            }
            if ($size > self::post_query_init_size)
            {
                $results = array_merge($results, $this -> handleTranslateResponse($source, $target, $texts, 'POST'));
            }
        }
        return (count($results) == 1) ? $results[0] : $results;
    }

    /**
     * Send and receive translate query
     * @param  string $source source lang
     * @param  string $target destination lang
     * @param  string|array $text   Text to translate
     * @param  string $method GET|POST
     * @return array
     */
    private function handleTranslateResponse($source, $target, $text, $method = 'GET')
    {
        $response = $this -> call('', array(
                'source' => $source,
                'target' => $target,
                'q'      => $text,
                ),
                $method
            );
        if (!isset($response -> translations))
        {
            throw new \Exception("Unable to find translations");
        }
        $results = array();
        foreach($response -> translations as $translations)
        {
            $results[] = $translations -> translatedText;
        }
        return $results;
    }
}