<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Faez84\DoctrinePerformanceGuardBundle\Collector\QueryLog;
use Faez84\DoctrinePerformanceGuardBundle\DBAL\GuardMiddleware;
use Faez84\DoctrinePerformanceGuardBundle\Detector\NPlusOneDetector;
use Faez84\DoctrinePerformanceGuardBundle\Detector\SqlNormalizer;
use Faez84\DoctrinePerformanceGuardBundle\EventSubscriber\GuardSubscriber;
use Psr\Log\LoggerInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autowire()->autoconfigure();

    $services->set(QueryLog::class);

    $services->set(SqlNormalizer::class);
    $services->set(NPlusOneDetector::class);

    $services->set(GuardMiddleware::class)
        ->arg('$log', service(QueryLog::class))
        ->tag('doctrine.dbal.middleware');

    $services->set(GuardSubscriber::class)
        ->tag('kernel.event_subscriber')
        ->arg('$enabled', param('doctrine_performance_guard.enabled'))
        ->arg('$mode', param('doctrine_performance_guard.mode'))
        ->arg('$maxQueries', param('doctrine_performance_guard.max_queries'))
        ->arg('$maxTotalTimeMs', param('doctrine_performance_guard.max_total_time_ms'))
        ->arg('$maxDuplicateQueryCount', param('doctrine_performance_guard.max_duplicate_query_count'))
        ->arg('$addDebugHeaders', param('doctrine_performance_guard.add_debug_headers'))
        ->arg('$applyToPaths', param('doctrine_performance_guard.apply_to_paths'))
        ->arg('$logger', service(LoggerInterface::class));
};
