<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Performance;

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Производительность API v1.3: call, bind, make, afterResolving, tagged API.
 */
#[CoversClass(Container::class)]
final class ContainerV13PerformanceTest extends TestCase
{
    private const CALL_ITERATIONS = 10000;

    private const MAKE_ITERATIONS = 5000;

    private const BIND_ITERATIONS = 1000;

    private const TAGGED_IDS_ITERATIONS = 10000;

    private const AFTER_RESOLVING_ITERATIONS = 1000;

    private const CALL_TIME_BUDGET_SECONDS = 0.75;

    private const MAKE_TIME_BUDGET_SECONDS = 1.0;

    private const BIND_TIME_BUDGET_SECONDS = 0.75;

    private const TAGGED_IDS_TIME_BUDGET_SECONDS = 0.35;

    private const AFTER_RESOLVING_TIME_BUDGET_SECONDS = 1.0;

    public function testCallWithExplicitParametersCompletesWithinBudget(): void
    {
        $container = new Container();
        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::CALL_ITERATIONS; ++$iteration) {
            $result = $container->call(
                static fn (int $value): int => $value,
                ['value' => $iteration],
            );

            self::assertSame($iteration, $result);
        }

        self::assertLessThan(self::CALL_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testMakeUncachedServiceCompletesWithinBudget(): void
    {
        $container = new Container();
        $container->set('proto', static fn (): stdClass => new stdClass());

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::MAKE_ITERATIONS; ++$iteration) {
            self::assertInstanceOf(stdClass::class, $container->make('proto'));
        }

        self::assertLessThan(self::MAKE_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testBindAndGetCompletesWithinBudget(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::BIND_ITERATIONS; ++$iteration) {
            $targetId = 'target.' . $iteration;
            $abstractId = 'abstract.' . $iteration;
            $container->set($targetId, new stdClass());
            $container->bind($abstractId, $targetId);
            self::assertInstanceOf(stdClass::class, $container->get($abstractId));
        }

        self::assertLessThan(self::BIND_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testGetTaggedIdsCompletesWithinBudget(): void
    {
        $container = new Container();

        for ($index = 0; $index < 200; ++$index) {
            $container->tag('handler.' . $index, 'handlers');
        }

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::TAGGED_IDS_ITERATIONS; ++$iteration) {
            self::assertCount(200, $container->getTaggedIds('handlers'));
        }

        self::assertLessThan(self::TAGGED_IDS_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testAfterResolvingOnFirstGetCompletesWithinBudget(): void
    {
        $container = new Container();

        for ($index = 0; $index < self::AFTER_RESOLVING_ITERATIONS; ++$index) {
            $serviceId = 'service.' . $index;
            $container->set($serviceId, new SimpleService());
            $container->afterResolving($serviceId, static function (): void {
            });
        }

        $startedAt = microtime(true);

        for ($index = 0; $index < self::AFTER_RESOLVING_ITERATIONS; ++$index) {
            self::assertInstanceOf(SimpleService::class, $container->get('service.' . $index));
        }

        self::assertLessThan(self::AFTER_RESOLVING_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }
}
