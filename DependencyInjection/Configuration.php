<?php

namespace Stadline\ExecutionCacheBundle\DependencyInjection;

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('stadline_execution_cache');

        $rootNode
            ->children()
                ->arrayNode('storage')->canBeDisabled()
                    ->children()
                        ->scalarNode('prefix')->defaultValue('exc_')->end()
                        ->scalarNode('default_ttl')->defaultValue(300)->end()
                        ->scalarNode('pool_adapter')->defaultValue('cache')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
