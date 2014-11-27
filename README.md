PryonGoogleTranslatorBundle
======================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/c394a83b-6c4a-43d6-ad54-fe379a12a297/big.png)](https://insight.sensiolabs.com/projects/c394a83b-6c4a-43d6-ad54-fe379a12a297)
[![Build Status](https://travis-ci.org/zeliard91/PryonGoogleTranslatorBundle.png)](https://travis-ci.org/zeliard91/PryonGoogleTranslatorBundle)

This bundle provides a symfony 2 service to interact with Google Translate API.
https://developers.google.com/translate/v2/getting_started

To be able to translate sentences, you have to enable billing on your Google 
Cloud Console https://developers.google.com/translate/v2/pricing?hl=fr

## Installation

Installation is a quick 3 step process:

1. Download PryonGoogleTranslatorBundle using composer
2. Enable the Bundle
3. Configure your application's config.yml

### Step 1: Download PryonGoogleTranslatorBundle using composer

Add PryonGoogleTranslatorBundle in your composer.json:

```js
{
    "require": {
        "zeliard91/google-translator-bundle": "dev-master"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update zeliard91/google-translator-bundle
```

Composer will install the bundle to your project's `vendor/zeliard91/google-translator-bundle` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Pryon\GoogleTranslatorBundle\PryonGoogleTranslatorBundle(),
    );
}
```

### Step3: Add your Google API Key in configuration

``` yaml
# app/config/config.yml

pryon_google_translator:
    google_api_key: MySecretKey
```

## Usage

The bundle provides a service to your app in order to call the Google Translate 
API in REST.

### Exemples

#### Get supported languages

``` php
<?php

$languages = $this -> get('pryon.google.translator') -> getSupportedLanguages();
// -> ['en','fr','de','it',...]
```

#### Translate sentences
``` php
<?php

$translation = $this -> get('pryon.google.translator') -> translate('en','fr','I love Symfony');
// -> "J'adore Symfony"

$translations = $this -> get('pryon.google.translator') -> translate('en','fr', array('I love Symfony', 'I like PHP'));
// -> ["J'adore Symfony", "J'aime PHP"]
```

Be aware that Google restricts the use of this service by limiting the size of the query 
at 5K caracteres with the POST method (and 2K in GET).
The number of queries is also limited to 128 if you pass an array for the third argument of the translate method.

The translate method detects if these limits are reached and call the API as many times 
as necessary which may result a long processing.


### Form Type

A "translatorlanguage" Form Type is also present in this bundle.
It is basically the same as the core "language" Form Type except from the choices list which is filled by the API.

``` php
<?php
// in your buildForm
    ->add('source', 'translatorlanguage', array(
        'required' => true,
        'label'    => 'Source language'
    ))
```

## Cache

You can cache the responses of the API with [one of subclass of Doctrine\Common\Cache\CacheProvider](https://github.com/doctrine/cache/tree/master/lib/Doctrine/Common/Cache)

This can be done by specifying what you want to cache and with what in the configuration file.
This is the default configuration :

``` yaml
# app/config/config.yml

pryon_google_translator:
    cache_provider: Doctrine\Common\Cache\PhpFileCache
    cache_arg: /tmp
    cache_calls:
        # get available languages method
        languages: true
        # translate method
        translate: false
```
