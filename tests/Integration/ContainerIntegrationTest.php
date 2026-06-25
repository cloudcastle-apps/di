<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Integration;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Интеграционные сценарии работы контейнера и цепочек зависимостей.
 */
#[CoversClass(Container::class)]
final class ContainerIntegrationTest extends TestCase
{
    public function testResolvesDependencyGraphThroughFactories(): void
    {
        $container = new Container();
        $container->set('repository', static fn (): stdClass => new stdClass());
        $container->set(
            'service',
            static function (ContainerInterface $container): stdClass {
                $service = new stdClass();
                $service->repository = $container->get('repository');

                return $service;
            },
        );

        $service = $container->get('service');

        self::assertInstanceOf(stdClass::class, $service);
        self::assertInstanceOf(stdClass::class, $service->repository);
        self::assertSame($container->get('repository'), $service->repository);
    }

    public function testPsrElevenWorkflowHasGetHasDefinition(): void
    {
        $container = new Container();
        $container->set('logger', static fn (): stdClass => new stdClass());

        self::assertTrue($container->has('logger'));
        self::assertTrue($container->hasDefinition('logger'));
        self::assertInstanceOf(stdClass::class, $container->get('logger'));
    }

    public function testMultipleContainersAreIsolated(): void
    {
        $first = new Container();
        $second = new Container();
        $first->set('token', static fn (): string => 'first');
        $second->set('token', static fn (): string => 'second');

        self::assertSame('first', $first->get('token'));
        self::assertSame('second', $second->get('token'));
    }
}
