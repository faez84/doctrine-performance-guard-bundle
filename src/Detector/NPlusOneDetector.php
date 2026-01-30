<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\Detector;

use Faez84\DoctrinePerformanceGuardBundle\Collector\QuerySample;

final class NPlusOneDetector
{
    public function __construct(private SqlNormalizer $normalizer)
    {
    }

    /**
     * @param QuerySample[] $queries
     * @return array<int, array{normalized:string,count:int,example:string}>
     */
    public function detect(array $queries, int $threshold): array
    {
        if ($threshold <= 0) {
            return [];
        }

        $counts = [];
        $examples = [];

        foreach ($queries as $q) {
            $norm = $this->normalizer->normalize($q->sql);
            $counts[$norm] = ($counts[$norm] ?? 0) + 1;

            if (!isset($examples[$norm])) {
                $examples[$norm] = $q->sql;
            }
        }

        $hits = [];
        foreach ($counts as $norm => $count) {
            if ($count >= $threshold) {
                $hits[] = [
                    'normalized' => $norm,
                    'count' => $count,
                    'example' => $examples[$norm] ?? $norm,
                ];
            }
        }

        usort($hits, fn($a, $b) => $b['count'] <=> $a['count']);

        return $hits;
    }
}
