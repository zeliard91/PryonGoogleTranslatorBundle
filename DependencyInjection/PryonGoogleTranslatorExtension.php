<?php

namespace Pryon\GoogleTranslatorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PryonGoogleTranslatorExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $container->setParameter('pryon_google_translator.google_api_key', $config['google_api_key']);
        $container->setParameter('pryon_google_translator.cache_provider_class', $config['cache_provider']);
        $container->setParameter('pryon_google_translator.cache_calls', $config['cache_calls']);
        $container->setParameter('pryon_google_translator.cache_arg', $config['cache_arg']);
    }
}
