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
        self::assertCount(2, $report['top_slowest']);
        self::assertSame('slow', $report['top_slowest'][0]['target']);
        self::assertTrue($report['top_slowest'][0]['cached']);
        self::assertSame('proto', $report['top_slowest'][1]['target']);
    }

    public function testResetClearsSamples(): void
    {
        $this->profiler->record('get', 'svc', 1.0);
        $this->profiler->reset();

        self::assertSame(0, $this->profiler->report()['sample_count']);
    }
}
