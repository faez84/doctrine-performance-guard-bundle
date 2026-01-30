<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Faez84\DoctrinePerformanceGuardBundle\Collector\QueryLog;

final class GuardMiddleware implements Middleware
{
    public function __construct(private QueryLog $log)
    {
    }

    public function wrap(Driver $driver): Driver
    {
        return new GuardDriver($driver, $this->log);
    }
}
