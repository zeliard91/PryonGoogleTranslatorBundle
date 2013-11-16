<?php

namespace Pryon\GoogleTranslatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pryon_google_translator');

        $rootNode
            ->children()
            ->scalarNode('google_api_key')->isRequired()->end()
            ->scalarNode('cache_provider')->defaultValue('Doctrine\Common\Cache\ArrayCache')->end()
            ->arrayNode('cache_calls')
                ->addDefaultsIfNotSet()
                ->children()
                ->booleanNode('translate')->defaultFalse()->end()
                ->booleanNode('languages')->defaultTrue()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
