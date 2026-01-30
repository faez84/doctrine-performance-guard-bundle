<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Faez84\DoctrinePerformanceGuardBundle\Collector\QueryLog;

final class GuardDriver extends AbstractDriverMiddleware
{
    public function __construct(Driver $driver, private QueryLog $log)
    {
        parent::__construct($driver);
    }

    public function connect(array $params): DriverConnection
    {
        $conn = parent::connect($params);
        return new GuardConnection($conn, $this->log);
    }
}
