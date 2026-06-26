<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Load;

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Нагрузочные сценарии API v1.3: addDefinitions, bind, call, make, afterResolving.
 */
#[CoversClass(Container::class)]
final class ContainerV13LoadTest extends TestCase
{
    private const int SERVICE_COUNT = 1500;

    private const float TIME_BUDGET_SECONDS = 3.0;

    public function testAddDefinitionsRegistersAndResolvesManyServices(): void
    {
        $container = new Container();
        /** @var array<string, stdClass> $definitions */
        $definitions = [];

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $definitions['bulk.' . $index] = new stdClass();
        }

        $container->addDefinitions($definitions);

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            self::assertInstanceOf(stdClass::class, $container->get('bulk.' . $index));
        }
    }

    public function testBindManyAliasesToRegisteredIds(): void
    {
        $container = new Container();

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->set('service.' . $index, new stdClass());
            $container->bind('abstract.' . $index, 'service.' . $index);
        }

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            self::assertSame(
                $container->get('service.' . $index),
                $container->get('abstract.' . $index),
            );
        }
    }

    public function testMakeManyPrototypesFromFactories(): void
    {
        $container = new Container();

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->set('proto.' . $index, static fn (): stdClass => new stdClass());
        }

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $first = $container->make('proto.' . $index);
            $second = $container->make('proto.' . $index);

            self::assertInstanceOf(stdClass::class, $first);
            self::assertInstanceOf(stdClass::class, $second);
            self::assertNotSame($first, $second);
        }
    }

    public function testCallManyTimesWithExplicitParameters(): void
    {
        $container = new Container();
        $iterations = self::SERVICE_COUNT * 2;

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < $iterations; ++$iteration) {
            $value = $container->call(
                static fn (int $number): int => $number,
                ['number' => $iteration],
            );

            self::assertSame($iteration, $value);
        }

        self::assertLessThan(self::TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }

    public function testAfterResolvingInvokesCallbackForEachFirstGet(): void
    {
        $container = new Container();
        $calls = 0;

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $serviceId = 'hooked.' . $index;
            $container->set($serviceId, new stdClass());
            $container->afterResolving($serviceId, static function () use (&$calls): void {
                ++$calls;
            });
        }

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->get('hooked.' . $index);
            $container->get('hooked.' . $index);
        }

        self::assertSame(self::SERVICE_COUNT, $calls);
    }

    public function testManyAliasesToAutowiredClass(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->autowire(SimpleService::class);

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->alias('alias.' . $index, SimpleService::class);
        }

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            self::assertInstanceOf(SimpleService::class, $container->get('alias.' . $index));
        }
    }
}
