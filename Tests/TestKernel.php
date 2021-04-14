<?php

namespace Pryon\GoogleTranslatorBundle\Tests;

use Pryon\GoogleTranslatorBundle\PryonGoogleTranslatorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new PryonGoogleTranslatorBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $apiKey = getenv('SYMFONY__PRYONTRANSLATOR__GOOGLEAPI');
        if (false === $apiKey) {
            throw new \Exception('Unable to load Google Translator API Key. Must be define as the environment variable SYMFONY__PRYONTRANSLATOR__GOOGLEAPI');
        }

        $container->loadFromExtension('framework', [
            'secret' => 'foo',
            'router' => [
                'utf8' => true,
            ],
            'form' => [
                'enabled' => true,
            ]
        ]);

        $container->loadFromExtension('pryon_google_translator', [
            'google_api_key' => $apiKey,
            'client_options' => [
                'headers' => [
                    'Referer' => 'https://test.makemyweb.fr',
                ]
            ]
        ]);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/cache'.spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/logs'.spl_object_hash($this);
    }
}