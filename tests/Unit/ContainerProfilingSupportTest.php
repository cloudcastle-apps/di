<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ContainerProfilingSupport;
use CloudCastle\DI\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ContainerProfilingSupport::class)]
final class ContainerProfilingSupportTest extends TestCase
{
    private ContainerProfilingSupport $support;

    protected function setUp(): void
    {
        $this->support = new ContainerProfilingSupport();
    }

    public function testEnabledFlagDefaultsToFalse(): void
    {
        self::assertFalse($this->support->isEnabled());
        self::assertFalse($this->support->report()['enabled']);
    }

    public function testEnableDisableToggleEnabledFlag(): void
    {
        $this->support->enable();

        self::assertTrue($this->support->isEnabled());
        self::assertTrue($this->support->report()['enabled']);

        $this->support->disable();

        self::assertFalse($this->support->isEnabled());
        self::assertFalse($this->support->report()['enabled']);
    }

    public function testMeasureWhenDisabledSkipsRecording(): void
    {
        $this->support->measure('get', 'svc', static fn (): stdClass => new stdClass());

        self::assertSame(0, $this->support->report()['sample_count']);
    }

    public function testMeasureWhenEnabledRecordsSample(): void
    {
        $this->support->enable();

        $this->support->measure('make', 'proto', static fn (): string => 'ok', cached: true);

        $report = $this->support->report();

        self::assertSame(1, $report['sample_count']);
        self::assertSame('make', $report['top_slowest'][0]['operation']);
        self::assertSame('proto', $report['top_slowest'][0]['target']);
        self::assertTrue($report['top_slowest'][0]['cached']);
    }

    public function testMeasureDefaultsCachedFlagToFalse(): void
    {
        $this->support->enable();

        $this->support->measure('get', 'svc', static fn (): int => 1);

        self::assertFalse($this->support->report()['top_slowest'][0]['cached']);
    }

    public function testMeasureRecordsSampleWhenCallbackThrows(): void
    {
        $this->support->enable();

        $thrown = false;

        try {
            $this->support->measure('get', 'broken', static function () use (&$thrown): never {
                $thrown = true;

                throw new ContainerException('fail');
            });
        } catch (ContainerException $containerException) {
            self::assertSame('fail', $containerException->getMessage());
        }

        self::assertTrue($thrown);
        self::assertSame(1, $this->support->report()['sample_count']);
    }

    public function testResetClearsProfilerSamples(): void
    {
        $this->support->enable();
        $this->support->measure('call', 'closure', static fn (): int => 1);
        $this->support->reset();

        self::assertSame(0, $this->support->report()['sample_count']);
    }

    public function testReportForwardsLimitToProfiler(): void
    {
        $this->support->enable();

        for ($index = 0; $index < 3; ++$index) {
            $this->support->measure('get', 'svc-' . $index, static fn (): int => $index);
        }

        self::assertCount(2, $this->support->report(limit: 2)['top_slowest']);
        self::assertCount(3, $this->support->report(limit: 0)['top_slowest']);
    }

    public function testMeasureRecordsElapsedTimeInMilliseconds(): void
    {
        $this->support->enable();

        $this->support->measure('get', 'slow', static function (): void {
            usleep(2000);
        });

        $sample = $this->support->report()['top_slowest'][0];

        self::assertSame('get', $sample['operation']);
        self::assertSame('slow', $sample['target']);
        self::assertGreaterThan(0.5, $sample['elapsed_ms']);
        self::assertLessThan(50.0, $sample['elapsed_ms']);
    }

    public function testDescribeCallableFormatsClassStringArrayCallable(): void
    {
        self::assertSame(
            self::class . '::exampleStatic',
            ContainerProfilingSupport::describeCallable([self::class, 'exampleStatic']),
        );
    }

    public static function exampleStatic(): void
    {
    }
}
