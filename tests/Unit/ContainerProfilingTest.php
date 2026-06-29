<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerProfilingSupport;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use CloudCastle\DI\Tests\Support\ContainerInternalAccess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ContainerProfilingSupport::class)]
final class ContainerProfilingTest extends TestCase
{
    public function testProfilingDisabledByDefault(): void
    {
        $container = new Container();

        self::assertFalse(ContainerInternalAccess::isProfilingEnabled($container));
        self::assertSame(0, ContainerInternalAccess::profileReport($container)['sample_count']);
    }

    public function testEnableProfilingRecordsGetMakeAndCall(): void
    {
        $container = new Container();
        $container->set('proto', static fn (): stdClass => new stdClass());
        $container->enableAutowiring();
        $container->set('app.clock', new Clock());
        $container->autowire(RequiredClockService::class);
        ContainerInternalAccess::enableProfiling($container);

        $container->get('proto');
        $container->get('proto');
        $container->make('proto');
        $container->call(static fn (Clock $clock): string => $clock::class);

        $report = ContainerInternalAccess::profileReport($container, limit: 10);

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
        ContainerInternalAccess::enableProfiling($container);

        $container->get('cached');
        $container->get('cached');

        $samples = ContainerInternalAccess::profileReport($container)['top_slowest'];

        self::assertFalse($samples[0]['cached']);
        self::assertTrue($samples[1]['cached']);

        $cachedSamples = array_values(array_filter(
            $samples,
            static fn (array $row): bool => $row['operation'] === 'get' && $row['cached'],
        ));

        self::assertCount(1, $cachedSamples);
    }

    public function testResetProfileClearsSamples(): void
    {
        $container = new Container();
        $container->set('svc', new stdClass());
        ContainerInternalAccess::enableProfiling($container);
        $container->get('svc');
        ContainerInternalAccess::resetProfile($container);

        self::assertSame(0, ContainerInternalAccess::profileReport($container)['sample_count']);
    }

    public function testDisableProfilingStopsRecording(): void
    {
        $container = new Container();
        $container->set('svc', new stdClass());
        ContainerInternalAccess::enableProfiling($container);
        $container->get('svc');
        ContainerInternalAccess::disableProfiling($container);
        $container->get('svc');

        self::assertSame(1, ContainerInternalAccess::profileReport($container)['sample_count']);
    }

    public function testProfileReportDefaultLimitReturnsTenEntries(): void
    {
        $container = new Container();
        ContainerInternalAccess::enableProfiling($container);

        for ($index = 0; $index < 11; ++$index) {
            $container->set('svc-' . $index, new stdClass());
            $container->get('svc-' . $index);
        }

        self::assertCount(10, ContainerInternalAccess::profileReport($container)['top_slowest']);
    }

    public function testProfileReportListsOperationNames(): void
    {
        $container = new Container();
        $container->set('proto', new stdClass());
        ContainerInternalAccess::enableProfiling($container);

        $container->get('proto');
        $container->make('proto');
        $container->call(static fn (): int => 1);

        $operations = array_keys(ContainerInternalAccess::profileReport($container)['by_operation']);

        self::assertEqualsCanonicalizing(['get', 'make', 'call'], $operations);
    }

    public function testProfileReportReflectsDisabledState(): void
    {
        $container = new Container();
        ContainerInternalAccess::enableProfiling($container);
        ContainerInternalAccess::disableProfiling($container);

        self::assertFalse(ContainerInternalAccess::profileReport($container)['enabled']);
    }

    public function testProfilingLifecycle(): void
    {
        $container = new Container();
        $container->set('svc', new stdClass());

        self::assertFalse(ContainerInternalAccess::isProfilingEnabled($container));

        ContainerInternalAccess::enableProfiling($container);
        self::assertTrue(ContainerInternalAccess::isProfilingEnabled($container));

        $container->get('svc');

        self::assertSame(1, ContainerInternalAccess::profileReport($container)['sample_count']);
        self::assertTrue(ContainerInternalAccess::profileReport($container)['enabled']);

        ContainerInternalAccess::disableProfiling($container);
        self::assertFalse(ContainerInternalAccess::isProfilingEnabled($container));

        $container->get('svc');
        self::assertSame(1, ContainerInternalAccess::profileReport($container)['sample_count']);

        ContainerInternalAccess::resetProfile($container);
        self::assertSame(0, ContainerInternalAccess::profileReport($container)['sample_count']);
        self::assertFalse(ContainerInternalAccess::isProfilingEnabled($container));
    }

    public function testProfileReportLimitIsForwarded(): void
    {
        $container = new Container();
        ContainerInternalAccess::enableProfiling($container);

        for ($index = 0; $index < 3; ++$index) {
            $container->set('svc-' . $index, new stdClass());
            $container->get('svc-' . $index);
        }

        self::assertCount(1, ContainerInternalAccess::profileReport($container, limit: 1)['top_slowest']);
        self::assertCount(3, ContainerInternalAccess::profileReport($container, limit: 0)['top_slowest']);
    }

    public function testGetProfileTargetUsesResolvedIdAfterAlias(): void
    {
        $container = new Container();
        $container->alias('alias.id', 'target.id');
        $container->set('target.id', new stdClass());
        ContainerInternalAccess::enableProfiling($container);

        $container->get('alias.id');

        self::assertSame('target.id', ContainerInternalAccess::profileReport($container)['top_slowest'][0]['target']);
    }

    public function testProfilingPreservesSingletonCaching(): void
    {
        $calls = 0;
        $container = new Container();
        $container->set('svc', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        ContainerInternalAccess::enableProfiling($container);

        /** @var object $first */
        $first = $container->get('svc');
        /** @var object $second */
        $second = $container->get('svc');

        self::assertSame($first, $second);
        self::assertSame(1, $calls);
    }

    public function testMakeProfileUsesMakeOperation(): void
    {
        $container = new Container();
        $container->set('proto', new stdClass());
        ContainerInternalAccess::enableProfiling($container);

        $container->make('proto');

        self::assertSame('make', ContainerInternalAccess::profileReport($container)['top_slowest'][0]['operation']);
    }

    public function testCallProfileTargetDescribesCallable(): void
    {
        $container = new Container();
        ContainerInternalAccess::enableProfiling($container);
        $container->call(static fn (): int => 1);

        self::assertSame('call', ContainerInternalAccess::profileReport($container)['top_slowest'][0]['operation']);
        self::assertSame('closure', ContainerInternalAccess::profileReport($container)['top_slowest'][0]['target']);
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
