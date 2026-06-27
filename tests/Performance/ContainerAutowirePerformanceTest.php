<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Performance;

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Производительность autowiring и call() с разрешением зависимостей.
 */
#[CoversClass(Container::class)]
final class ContainerAutowirePerformanceTest extends TestCase
{
    private const CACHED_GET_ITERATIONS = 10000;

    private const COLD_AUTOWIRE_ITERATIONS = 500;

    private const AUTOWIRE_CALL_ITERATIONS = 2000;

    private const CACHED_GET_TIME_BUDGET_SECONDS = 1.5;

    private const COLD_AUTOWIRE_TIME_BUDGET_SECONDS = 2.0;

    private const AUTOWIRE_CALL_TIME_BUDGET_SECONDS = 2.0;

    public function testCachedAutowireGetCompletesWithinBudget(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->get(SimpleService::class);

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::CACHED_GET_ITERATIONS; ++$iteration) {
            self::assertInstanceOf(SimpleService::class, $container->get(SimpleService::class));
        }

        self::assertLessThan(self::CACHED_GET_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testColdAutowireGetCompletesWithinBudget(): void
    {
        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::COLD_AUTOWIRE_ITERATIONS; ++$iteration) {
            $container = new Container();
            $container->enableAutowiring();
            $container->set('app.clock', new Clock());
            $container->autowire(RequiredClockService::class);

            self::assertInstanceOf(RequiredClockService::class, $container->get(RequiredClockService::class));
        }

        self::assertLessThan(self::COLD_AUTOWIRE_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testCallWithAutowireDependencyCompletesWithinBudget(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::AUTOWIRE_CALL_ITERATIONS; ++$iteration) {
            $class = $container->call(
                static fn (SimpleService $service): string => $service::class,
            );

            self::assertSame(SimpleService::class, $class);
        }

        self::assertLessThan(self::AUTOWIRE_CALL_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }
}
