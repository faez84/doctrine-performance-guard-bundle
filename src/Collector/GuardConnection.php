<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Faez84\DoctrinePerformanceGuardBundle\Collector\QueryLog;

final class GuardConnection extends AbstractConnectionMiddleware
{
    public function __construct(Connection $connection, private QueryLog $log)
    {
        parent::__construct($connection);
    }

    public function prepare(string $sql): \Doctrine\DBAL\Driver\Statement
    {
        return parent::prepare($sql);
    }

    public function query(string $sql): Result
    {
        $start = hrtime(true);
        try {
            return parent::query($sql);
        } finally {
            $this->log->add($sql, $this->elapsedMs($start));
        }
    }

    public function exec(string $sql): int|string
    {
        $start = hrtime(true);
        try {
            return parent::exec($sql);
        } finally {
            $this->log->add($sql, $this->elapsedMs($start));
        }
    }

    private function elapsedMs(int $startNs): float
    {
        return (hrtime(true) - $startNs) / 1_000_000;
    }
}
