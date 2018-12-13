<?php

namespace KRG\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('krg_core');

        $rootNode->children()
                    ->arrayNode('export')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('csv')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('delimiter')->defaultValue(',')->end()
                                    ->scalarNode('enclosure')->defaultValue('"')->end()
                                    ->scalarNode('escape_char')->defaultValue('\\')->end()
                                ->end()
                            ->end()
                            ->arrayNode('xls')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('Author')->defaultValue('Kangourouge')->end()
                                    ->scalarNode('Company')->defaultValue('Kangourouge')->end()
                                    ->arrayNode('Colors')
                                        ->arrayPrototype()
                                        ->end()
                                        ->defaultValue(['#000000', '#FFFFFF', '#C1C1C1'])
                                    ->end()
                                    ->scalarNode('WindowHeight')->defaultValue(16080)->end()
                                    ->scalarNode('WindowWidth')->defaultValue(25600)->end()
                                    ->scalarNode('WindowTopX')->defaultValue(29976)->end()
                                    ->scalarNode('WindowTopY')->defaultValue(1920)->end()
                                    ->scalarNode('ProtectStructure')->defaultValue('False')->end()
                                    ->scalarNode('ProtectWindows')->defaultValue('False')->end()
                                    ->scalarNode('DisplayInkNotes')->defaultValue('False')->end()
                                    ->scalarNode('FontName')->defaultValue('Arial')->end()
                                    ->scalarNode('Family')->defaultValue('Arial')->end()
                                    ->scalarNode('Color')->defaultValue('#000000')->end()
                                    ->scalarNode('THeadColor')->defaultValue('#FFFFFF')->end()
                                    ->scalarNode('THeadBackgroundColor')->defaultValue('#757575')->end()
                                    ->scalarNode('TBodyColor')->defaultValue('#000000')->end()
                                    ->scalarNode('TBodyBackgroundColor')->defaultValue('#FFFFFF')->end()
                                    ->scalarNode('TFootColor')->defaultValue('#DDDDDD')->end()
                                    ->scalarNode('TFootBackgroundColor')->defaultValue('#757575')->end()
                                    ->scalarNode('Logo')->defaultValue('/frontend/images/logo.png')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
        return $treeBuilder;
    }
}
