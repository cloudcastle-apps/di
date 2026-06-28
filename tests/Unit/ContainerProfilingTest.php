<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerProfilingSupport;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
#[CoversClass(ContainerProfilingSupport::class)]
final class ContainerProfilingTest extends TestCase
{
    public function testProfilingDisabledByDefault(): void
    {
        $container = new Container();

        self::assertFalse($container->isProfilingEnabled());
        self::assertSame(0, $container->profileReport()['sample_count']);
    }

    public function testEnableProfilingRecordsGetMakeAndCall(): void
    {
        $container = new Container();
        $container->set('proto', static fn (): stdClass => new stdClass());
        $container->enableAutowiring();
        $container->set('app.clock', new Clock());
        $container->autowire(RequiredClockService::class);
        $container->enableProfiling();

        $container->get('proto');
        $container->get('proto');
        $container->make('proto');
        $container->call(static fn (Clock $clock): string => $clock::class);

        $report = $container->profileReport(limit: 10);

        self::assertTrue($report['enabled']);
        self::assertSame(5, $report['sample_count']);
        self::assertArrayHasKey('get', $report['by_operation']);
        self::assertArrayHasKey('make', $report['by_operation']);
        self::assertArrayHasKey('call', $report['by_operation']);
        self::assertSame(3, $report['by_operation']['get']['count']);
        self::assertSame(1, $report['by_operation']['make']['count']);
        self::assertSame(1, $report['by_operation']['call']['count']);
        self::assertTrue($report['top_slowest'][0]['elapsed_ms'] >= $report['top_slowest'][1]['elapsed_ms']);
    }

    public function testCachedGetIsMarkedInProfileReport(): void
    {
        $container = new Container();
        $container->set('cached', new stdClass());
        $container->enableProfiling();

        $container->get('cached');
        $container->get('cached');

        $cachedSamples = array_values(array_filter(
            $container->profileReport()['top_slowest'],
            static fn (array $row): bool => $row['operation'] === 'get' && $row['cached'],
        ));

        self::assertCount(1, $cachedSamples);
    }

    public function testResetProfileClearsSamples(): void
    {
        $container = new Container();
        $container->set('svc', new stdClass());
        $container->enableProfiling();
        $container->get('svc');
        $container->resetProfile();

        self::assertSame(0, $container->profileReport()['sample_count']);
    }

    public function testDisableProfilingStopsRecording(): void
    {
        $container = new Container();
        $container->set('svc', new stdClass());
        $container->enableProfiling();
        $container->get('svc');
        $container->disableProfiling();
        $container->get('svc');

        self::assertSame(1, $container->profileReport()['sample_count']);
    }

    public function testProfileReportDefaultLimitReturnsTenEntries(): void
    {
        $container = new Container();
        $container->enableProfiling();

        for ($index = 0; $index < 11; ++$index) {
            $container->set('svc-' . $index, new stdClass());
            $container->get('svc-' . $index);
        }

        self::assertCount(10, $container->profileReport()['top_slowest']);
    }

    public function testDescribeCallableFormatsTargets(): void
    {
        self::assertSame('closure', ContainerProfilingSupport::describeCallable(static fn (): int => 1));
        self::assertSame('strlen', ContainerProfilingSupport::describeCallable('strlen'));
        self::assertSame(
            self::class . '::exampleStatic',
            ContainerProfilingSupport::describeCallable([self::class, 'exampleStatic']),
        );

        $invokable = new class () {
            public function __invoke(): void
            {
            }
        };

        self::assertSame($invokable::class . '::__invoke', ContainerProfilingSupport::describeCallable($invokable));
    }

    public static function exampleStatic(): void
    {
    }
}
