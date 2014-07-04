<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingAutoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * Returns the config tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('cmf_routing_auto')
            ->children()
                ->booleanNode('auto_mapping')->defaultTrue()->end()
                ->arrayNode('mapping')
                    ->fixXmlConfig('resource')
                    ->children()
                        ->arrayNode('resources')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) { return array('path' => $v); })
                                ->end()
                                ->children()
                                    ->scalarNode('path')->isRequired()->end()
                                    ->scalarNode('type')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end() // directories
                    ->end()
                ->end() // mapping
                ->append($this->getPersistenceNode())
            ->end();

        return $treeBuilder;
    }

    protected function getPersistenceNode()
    {
        $builder = new TreeBuilder();
        $persistence = $builder->root('persistence');

        $persistence
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('phpcr')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('route_basepath')->defaultValue('/cms/routes')->end()
                    ->end()
                ->end() // phpcr
            ->end();

        return $persistence;
    }
}