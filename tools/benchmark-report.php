#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Формирует отчёт по нагрузочным и performance-сценариям.
 *
 * Использование:
 *   php tools/benchmark-report.php [--markdown] [--json] [--check] [--tolerance=1.5] [--output-dir=var/benchmark]
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

/** @param list<string> $argv */
function benchmark_parse_output_dir(array $argv): ?string
{
    foreach ($argv as $argument) {
        if (str_starts_with($argument, '--output-dir=')) {
            $path = substr($argument, strlen('--output-dir='));

            return $path !== '' ? $path : null;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $payload
 */
function benchmark_write_json(string $directory, array $payload): void
{
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException(\sprintf('Не удалось создать каталог "%s".', $directory));
    }

    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    file_put_contents($directory . '/benchmark.json', $encoded);
}

/**
 * @param list<array<string, mixed>> $results
 */
function benchmark_render_markdown(array $results, float $tolerance, bool $check): string
{
    $phpVersion = PHP_VERSION;
    $date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');
    $lines = [];
    $lines[] = '# Референсный прогон бенчмарков';
    $lines[] = '';
    $lines[] = \sprintf(
        'Среда: PHP %s, UTC %s. Метрики: wall time, p50/p95/p99, ops/sec, memory peak.',
        $phpVersion,
        $date,
    );
    $lines[] = $check
        ? \sprintf(' Регрессия: elapsed ×%.1f, memory peak ×%.1f.', $tolerance, $tolerance)
        : '';
    $lines[] = '';
    $lines[] = '| Сценарий | Iter | Wall (ms) | p50 | p95 | p99 | ops/sec | Mem (MB) | Budget (ms) | Статус |';
    $lines[] = '|----------|------|-----------|-----|-----|-----|---------|----------|-------------|--------|';

    foreach ($results as $row) {
        $budget = $row['budget_ms'] !== null ? (string) $row['budget_ms'] : '—';
        $limitMs = $row['budget_ms'] !== null ? $row['budget_ms'] * $tolerance : null;
        $memoryLimit = $row['memory_budget_mb'] !== null ? $row['memory_budget_mb'] * $tolerance : null;
        $timeOk = $limitMs === null || $row['elapsed_ms'] <= $limitMs;
        $memoryOk = $memoryLimit === null || $row['memory_peak_mb'] <= $memoryLimit;
        $status = $timeOk && $memoryOk ? 'OK' : 'FAIL';

        $lines[] = \sprintf(
            '| %s | %d | %.2f | %.4f | %.4f | %.4f | %.0f | %.2f | %s | %s |',
            $row['label'],
            $row['iterations'],
            $row['elapsed_ms'],
            $row['p50_ms'],
            $row['p95_ms'],
            $row['p99_ms'],
            $row['ops_sec'],
            $row['memory_peak_mb'],
            $budget,
            $status,
        );
    }

    return implode("\n", $lines) . "\n";
}

/**
 * @param list<array<string, mixed>> $failed
 */
function benchmark_render_regressions(array $failed, float $tolerance, bool $markdown): string
{
    if ($failed === []) {
        return '';
    }

    $lines = [];

    if ($markdown) {
        $lines[] = '';
        $lines[] = '## Регрессия';
        $lines[] = '';
    } else {
        $lines[] = '';
        $lines[] = "Benchmark regression (tolerance ×{$tolerance}):";
    }

    foreach ($failed as $row) {
        if ($row['metric'] === 'elapsed_ms') {
            $lines[] = \sprintf(
                '- %s [elapsed]: %.2f ms > limit %.2f ms (budget %.2f ms × %s)',
                $row['label'],
                $row['elapsed_ms'],
                $row['limit_ms'],
                $row['budget_ms'],
                $tolerance,
            );
        } elseif ($row['metric'] === 'memory_peak_mb') {
            $lines[] = \sprintf(
                '- %s [memory]: %.2f MB > limit %.2f MB (budget %.2f MB × %s)',
                $row['label'],
                $row['memory_peak_mb'],
                $row['memory_limit_mb'],
                $row['memory_budget_mb'],
                $tolerance,
            );
        }
    }

    return implode("\n", $lines) . "\n";
}

$arguments = $argv ?? [];
$markdown = \in_array('--markdown', $arguments, true);
$json = \in_array('--json', $arguments, true);
$check = \in_array('--check', $arguments, true);
$tolerance = benchmark_parse_tolerance($arguments);
$outputDir = benchmark_parse_output_dir($arguments);
$results = benchmark_collect();
$payload = benchmark_build_report_payload($results, $tolerance);

if ($json) {
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    echo $encoded;

    if ($outputDir !== null) {
        benchmark_write_json($outputDir, $payload);
    }
} elseif ($markdown) {
    echo benchmark_render_markdown($results, $tolerance, $check);
} else {
    $phpVersion = PHP_VERSION;
    $date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');
    echo "Benchmark report (PHP {$phpVersion}, {$date})\n\n";

    foreach ($results as $row) {
        $budget = $row['budget_ms'] !== null ? \sprintf(' / budget %.2f ms', $row['budget_ms']) : '';
        echo \sprintf(
            "- %s: %d iterations, %.2f ms, p99 %.4f ms, %.0f ops/sec, peak %.2f MB%s\n",
            $row['label'],
            $row['iterations'],
            $row['elapsed_ms'],
            $row['p99_ms'],
            $row['ops_sec'],
            $row['memory_peak_mb'],
            $budget,
        );
    }
}

if ($outputDir !== null && !$json) {
    benchmark_write_json($outputDir, $payload);

    if ($markdown) {
        file_put_contents($outputDir . '/benchmark.md', benchmark_render_markdown($results, $tolerance, $check));
    }
}

if ($check) {
    $failed = benchmark_find_regressions($results, $tolerance);
    $regressionText = benchmark_render_regressions($failed, $tolerance, $markdown || ($outputDir !== null && !$json));

    if ($regressionText !== '') {
        echo $regressionText;
    }

    if ($failed !== []) {
        exit(1);
    }
}

exit(0);
