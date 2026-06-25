<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Load;

use CloudCastle\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Нагрузочные сценарии с большим числом регистраций и разрешений сервисов.
 */
#[CoversClass(Container::class)]
final class ContainerLoadTest extends TestCase
{
    private const int SERVICE_COUNT = 2000;

    public function testRegistersAndResolvesManyServices(): void
    {
        $container = new Container();

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->set('service.' . $index, new stdClass());
        }

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            self::assertInstanceOf(stdClass::class, $container->get('service.' . $index));
        }
    }

    public function testResolvesManySingletonFactoriesOnce(): void
    {
        $container = new Container();
        $calls = 0;

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->set(
                'factory.' . $index,
                static function () use (&$calls): stdClass {
                    ++$calls;

                    return new stdClass();
                },
            );
        }

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->get('factory.' . $index);
            $container->get('factory.' . $index);
        }

        self::assertSame(self::SERVICE_COUNT, $calls);
    }

    public function testCompletesBulkResolutionWithinTimeBudget(): void
    {
        $container = new Container();
        $iterations = self::SERVICE_COUNT * 2;

        for ($index = 0; $index < self::SERVICE_COUNT; ++$index) {
            $container->set('bulk.' . $index, static fn (): int => $index);
        }

        $startedAt = microtime(true);

        for ($iteration = 0; $iteration < $iterations; ++$iteration) {
            $serviceId = 'bulk.' . ($iteration % self::SERVICE_COUNT);
            self::assertSame($iteration % self::SERVICE_COUNT, $container->get($serviceId));
        }

        $elapsedSeconds = microtime(true) - $startedAt;

        self::assertLessThan(2.0, $elapsedSeconds);
    }
}
