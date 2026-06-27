#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Формирует markdown-отчёт по нагрузочным и performance-сценариям (фактическое время на текущей машине).
 *
 * Использование:
 *   php tools/benchmark-report.php [--markdown] [--check] [--tolerance=1.5]
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/benchmark-lib.php';

/** @param list<string> $argv */
function benchmark_parse_tolerance(array $argv): float
{
    foreach ($argv as $argument) {
        if (str_starts_with($argument, '--tolerance=')) {
            $value = (float) substr($argument, strlen('--tolerance='));

            return $value > 0 ? $value : 1.0;
        }
    }

    $environment = getenv('BENCHMARK_TOLERANCE');

    if ($environment !== false && is_numeric($environment)) {
        $value = (float) $environment;

        return $value > 0 ? $value : 1.0;
    }

    return 1.0;
}

$arguments = $argv ?? [];
$markdown = \in_array('--markdown', $arguments, true);
$check = \in_array('--check', $arguments, true);
$tolerance = benchmark_parse_tolerance($arguments);
$results = benchmark_collect();
$phpVersion = PHP_VERSION;
$date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');

if ($markdown) {
    echo "# Референсный прогон бенчмарков\n\n";
    echo "Среда: PHP {$phpVersion}, UTC {$date}. Пороги — из performance/load-тестов PHPUnit";
    echo $check ? " (tolerance ×{$tolerance}).\n\n" : ".\n\n";
    echo "| Сценарий | Итераций | Порог (мс) | Факт (мс) | Статус |\n";
    echo "|----------|----------|------------|-----------|--------|\n";

    foreach ($results as $row) {
        $budget = $row['budget_ms'] !== null ? (string) $row['budget_ms'] : '—';
        $limitMs = $row['budget_ms'] !== null ? $row['budget_ms'] * $tolerance : null;
        $status = $limitMs === null || $row['elapsed_ms'] <= $limitMs ? 'OK' : 'FAIL';

        echo \sprintf(
            "| %s | %d | %s | %.2f | %s |\n",
            $row['label'],
            $row['iterations'],
            $budget,
            $row['elapsed_ms'],
            $status,
        );
    }
} else {
    echo "Benchmark report (PHP {$phpVersion}, {$date})\n\n";

    foreach ($results as $row) {
        $budget = $row['budget_ms'] !== null ? \sprintf(' / budget %.2f ms', $row['budget_ms']) : '';
        echo \sprintf(
            "- %s: %d iterations, %.2f ms%s\n",
            $row['label'],
            $row['iterations'],
            $row['elapsed_ms'],
            $budget,
        );
    }
}

if ($check) {
    $failed = benchmark_find_regressions($results, $tolerance);

    if ($failed !== []) {
        if (!$markdown) {
            echo "\nBenchmark regression (tolerance ×{$tolerance}):\n";
        } else {
            echo "\n## Регрессия\n\n";
        }

        foreach ($failed as $row) {
            echo \sprintf(
                "- %s: %.2f ms > limit %.2f ms (budget %.2f ms × %s)\n",
                $row['label'],
                $row['elapsed_ms'],
                $row['limit_ms'],
                $row['budget_ms'],
                $tolerance,
            );
        }

        exit(1);
    }
}

exit(0);
