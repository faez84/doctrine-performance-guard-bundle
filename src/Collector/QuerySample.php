<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\Collector;

final class QuerySample
{
    public function __construct(
        public readonly string $sql,
        public readonly float $durationMs
    ) {}
}
