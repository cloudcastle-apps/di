<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Performance;

use CloudCastle\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Тесты производительности отдельных операций контейнера.
 */
#[CoversClass(Container::class)]
#[Group('performance')]
final class ContainerPerformanceTest extends TestCase
{
    private const GET_ITERATIONS = 10000;

    private const HAS_ITERATIONS = 10000;

    private const SET_ITERATIONS = 5000;

    private const GET_TIME_BUDGET_SECONDS = 0.5;

    private const HAS_TIME_BUDGET_SECONDS = 0.5;

    private const SET_TIME_BUDGET_SECONDS = 0.5;

    public function testGetCachedServiceCompletesWithinBudget(): void
    {
        $container = new Container();
        $container->set('cached', new stdClass());

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::GET_ITERATIONS; ++$iteration) {
            self::assertInstanceOf(stdClass::class, $container->get('cached'));
        }

        self::assertLessThan(self::GET_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testHasExistingServiceCompletesWithinBudget(): void
    {
        $container = new Container();
        $container->set('cached', new stdClass());

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::HAS_ITERATIONS; ++$iteration) {
            self::assertTrue($container->has('cached'));
        }

        self::assertLessThan(self::HAS_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testHasDefinitionCompletesWithinBudget(): void
    {
        $container = new Container();
        $container->set('cached', new stdClass());

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::HAS_ITERATIONS; ++$iteration) {
            self::assertTrue($container->hasDefinition('cached'));
        }

        self::assertLessThan(self::HAS_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testSetServiceCompletesWithinBudget(): void
    {
        $container = new Container();
        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < self::SET_ITERATIONS; ++$iteration) {
            $container->set('dynamic.' . $iteration, new stdClass());
        }

        self::assertLessThan(self::SET_TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }
}
