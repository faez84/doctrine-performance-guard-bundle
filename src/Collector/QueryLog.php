<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\Collector;

final class QueryLog
{
    /** @var QuerySample[] */
    private array $queries = [];

    private float $totalMs = 0.0;

    public function reset(): void
    {
        $this->queries = [];
        $this->totalMs = 0.0;
    }

    public function add(string $sql, float $durationMs): void
    {
        $this->queries[] = new QuerySample($sql, $durationMs);
        $this->totalMs += $durationMs;
    }

    /** @return QuerySample[] */
    public function all(): array
    {
        return $this->queries;
    }

    public function count(): int
    {
        return \count($this->queries);
    }

    public function totalMs(): float
    {
        return $this->totalMs;
    }
}
