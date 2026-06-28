<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/tools/benchmark-lib.php';

final class BenchmarkLibTest extends TestCase
{
    public function testPercentileReturnsMedian(): void
    {
        self::assertSame(2.0, benchmark_percentile([1.0, 2.0, 3.0, 4.0], 50));
    }

    public function testPercentileReturnsHighTail(): void
    {
        self::assertSame(9.0, benchmark_percentile([1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0], 95));
    }

    public function testFindRegressionsDetectsElapsedAndMemory(): void
    {
        $results = [
            [
                'label' => 'slow',
                'iterations' => 100,
                'budget_ms' => 100.0,
                'memory_budget_mb' => 10.0,
                'elapsed_ms' => 200.0,
                'p50_ms' => 1.0,
                'p95_ms' => 2.0,
                'p99_ms' => 3.0,
                'ops_sec' => 500.0,
                'memory_peak_mb' => 20.0,
            ],
        ];

        $failed = benchmark_find_regressions($results, 1.5);

        self::assertCount(2, $failed);
        self::assertSame('elapsed_ms', $failed[0]['metric']);
        self::assertSame('memory_peak_mb', $failed[1]['metric']);
    }

    public function testBuildReportPayloadIncludesMetadata(): void
    {
        $payload = benchmark_build_report_payload([], 1.5);

        self::assertSame(1.5, $payload['tolerance']);
        self::assertSame(PHP_VERSION, $payload['php_version']);
        self::assertIsArray($payload['scenarios']);
        self::assertIsArray($payload['regressions']);
    }
}
