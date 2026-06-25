<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Integration;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Сценарии с глубокими цепочками и взаимными зависимостями.
 */
#[CoversClass(Container::class)]
final class ContainerDependencyChainTest extends TestCase
{
    public function testDeepDependencyChainResolvesFromLeafToRoot(): void
    {
        $container = new Container();
        $container->set('level.three', static fn (): stdClass => new stdClass());
        $container->set(
            'level.two',
            static function (ContainerInterface $container): stdClass {
                $service = new stdClass();
                $service->child = $container->get('level.three');

                return $service;
            },
        );
        $container->set(
            'level.one',
            static function (ContainerInterface $container): stdClass {
                $service = new stdClass();
                $service->child = $container->get('level.two');

                return $service;
            },
        );

        $root = $container->get('level.one');
        self::assertInstanceOf(stdClass::class, $root);

        $levelTwo = $root->child;
        self::assertInstanceOf(stdClass::class, $levelTwo);

        $levelThree = $levelTwo->child;
        self::assertInstanceOf(stdClass::class, $levelThree);
        self::assertSame($container->get('level.three'), $levelThree);
    }

    public function testSharedDependencyIsResolvedOnceInGraph(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('shared', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->set(
            'consumer.a',
            static function (ContainerInterface $container): stdClass {
                $shared = $container->get('shared');
                self::assertInstanceOf(stdClass::class, $shared);

                return $shared;
            },
        );
        $container->set(
            'consumer.b',
            static function (ContainerInterface $container): stdClass {
                $shared = $container->get('shared');
                self::assertInstanceOf(stdClass::class, $shared);

                return $shared;
            },
        );

        $first = $container->get('consumer.a');
        $second = $container->get('consumer.b');

        self::assertInstanceOf(stdClass::class, $first);
        self::assertInstanceOf(stdClass::class, $second);
        self::assertSame($first, $second);
        self::assertSame(1, $calls);
    }
}
