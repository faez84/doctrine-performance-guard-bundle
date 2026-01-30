<?php

declare(strict_types=1);

namespace Faez84\DoctrinePerformanceGuardBundle\Detector;

final class SqlNormalizer
{
    public function normalize(string $sql): string
    {
        $s = strtolower($sql);

        // collapse whitespace
        $s = preg_replace('/\s+/', ' ', $s ?? '') ?? $s;

        // replace quoted strings with ?
        $s = preg_replace("/'([^'\\\\]|\\\\.)*'/", "?", $s) ?? $s;

        // replace numbers with ?
        $s = preg_replace('/\b\d+\b/', '?', $s) ?? $s;

        // replace UUID-like strings with ?
        $s = preg_replace('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/', '?', $s) ?? $s;

        // normalize IN (...) lists
        $s = preg_replace('/\bin\s*\(([^)]*)\)/', 'in (?)', $s) ?? $s;

        return trim($s);
    }
}
