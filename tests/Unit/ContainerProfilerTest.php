<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ContainerProfiler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerProfiler::class)]
final class ContainerProfilerTest extends TestCase
{
    private ContainerProfiler $profiler;

    protected function setUp(): void
    {
        $this->profiler = new ContainerProfiler();
    }

    public function testReportIsEmptyForNewProfiler(): void
    {
        self::assertSame(
            [
                'sample_count' => 0,
                'total_ms' => 0.0,
                'by_operation' => [],
                'top_slowest' => [],
            ],
            $this->profiler->report(),
        );
    }

    public function testReportAggregatesByOperationAndSortsTopSlowest(): void
    {
        $this->profiler->record('get', 'fast', 1.0);
        $this->profiler->record('get', 'slow', 5.0, cached: true);
        $this->profiler->record('make', 'proto', 3.0);
        $this->profiler->record('call', 'closure', 2.0);

        $report = $this->profiler->report(limit: 2);

        self::assertSame(4, $report['sample_count']);
        self::assertSame(11.0, $report['total_ms']);
        self::assertSame(2, $report['by_operation']['get']['count']);
        self::assertSame(6.0, $report['by_operation']['get']['total_ms']);
        self::assertSame(3.0, $report['by_operation']['get']['avg_ms']);
        self::assertSame(1, $report['by_operation']['make']['count']);
        self::assertSame(3.0, $report['by_operation']['make']['total_ms']);
        self::assertSame(3.0, $report['by_operation']['make']['avg_ms']);
        self::assertSame(1, $report['by_operation']['call']['count']);
        self::assertSame(2.0, $report['by_operation']['call']['total_ms']);
        self::assertSame(2.0, $report['by_operation']['call']['avg_ms']);
        self::assertCount(2, $report['top_slowest']);
        self::assertSame('slow', $report['top_slowest'][0]['target']);
        self::assertTrue($report['top_slowest'][0]['cached']);
        self::assertSame('proto', $report['top_slowest'][1]['target']);
    }

    public function testReportSortsAllSamplesWhenLimitIsZero(): void
    {
        $this->profiler->record('get', 'fast', 1.0);
        $this->profiler->record('get', 'slow', 5.0);
        $this->profiler->record('get', 'mid', 3.0);

        self::assertSame(
            ['slow', 'mid', 'fast'],
            array_column($this->profiler->report(limit: 0)['top_slowest'], 'target'),
        );
    }

    public function testResetClearsSamples(): void
    {
        $this->profiler->record('get', 'svc', 1.0);
        $this->profiler->reset();

        self::assertSame(0, $this->profiler->report()['sample_count']);
    }

    public function testRecordRoundsElapsedMilliseconds(): void
    {
        $this->profiler->record('get', 'svc', 1.23456789);

        self::assertSame(1.2346, $this->profiler->report()['top_slowest'][0]['elapsed_ms']);
    }

    public function testReportWithLimitZeroReturnsAllSamples(): void
    {
        $this->profiler->record('get', 'a', 1.0);
        $this->profiler->record('get', 'b', 2.0);
        $this->profiler->record('get', 'c', 3.0);

        self::assertCount(3, $this->profiler->report(limit: 0)['top_slowest']);
    }

    public function testReportTotalMsUsesFourDecimalRounding(): void
    {
        $this->profiler->record('get', 'a', 1.11115);
        $this->profiler->record('get', 'b', 2.22225);

        $report = $this->profiler->report();

        self::assertSame(3.3335, $report['total_ms']);
        self::assertSame(3.3335, $report['by_operation']['get']['total_ms']);
        self::assertSame(1.6668, $report['by_operation']['get']['avg_ms']);
    }

    public function testTopSlowestOrderIsStrictlyDescending(): void
    {
        foreach ([10.0, 7.5, 5.0, 2.5, 1.0] as $index => $elapsedMs) {
            $this->profiler->record('get', 'svc-' . $index, $elapsedMs);
        }

        self::assertSame(
            [10.0, 7.5, 5.0, 2.5, 1.0],
            array_column($this->profiler->report(limit: 0)['top_slowest'], 'elapsed_ms'),
        );
    }

    public function testRecordStoresCachedFlagFalseByDefault(): void
    {
        $this->profiler->record('get', 'svc', 1.0);

        self::assertFalse($this->profiler->report()['top_slowest'][0]['cached']);
    }

    public function testReportDefaultLimitKeepsTenSlowestEntries(): void
    {
        for ($index = 0; $index < 11; ++$index) {
            $this->profiler->record('get', 'svc-' . $index, (float) $index);
        }

        self::assertCount(10, $this->profiler->report()['top_slowest']);
        self::assertSame(10.0, $this->profiler->report()['top_slowest'][0]['elapsed_ms']);
    }
}
