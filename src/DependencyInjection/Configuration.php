<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tb = new TreeBuilder('doctrine_performance_guard');
        $root = $tb->getRootNode();

        $root
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->enumNode('mode')->values(['log', 'exception'])->defaultValue('log')->end()

                ->integerNode('max_queries')->defaultValue(80)->min(0)->end()
                ->integerNode('max_total_time_ms')->defaultValue(250)->min(0)->end()
                ->integerNode('max_duplicate_query_count')->defaultValue(15)->min(0)->end()

                ->booleanNode('add_debug_headers')->defaultTrue()->end()

                ->arrayNode('apply_to_paths')
                    ->scalarPrototype()->end()
                    ->defaultValue(['/api'])
                ->end()
            ->end();

        return $tb;
    }
}
