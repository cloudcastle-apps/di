<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\ServiceInstanceResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\CircularA;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[CoversClass(ServiceInstanceResolver::class)]
final class ServiceInstanceResolverTest extends TestCase
{
    public function testResolveReturnsCachedSingleton(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $cached = new stdClass();
        $definitions = [];
        $resolved = ['service' => $cached];
        $resolving = [];

        $result = $resolver->resolve(
            'service',
            true,
            $definitions,
            $resolved,
            $resolving,
            [],
            $this->neverAutowire(...),
            $this->newStdClass(...),
        );

        self::assertSame($cached, $result);
    }

    public function testResolveFromScalarDefinitionCachesSingleton(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = ['config' => 'value'];
        $resolved = [];
        $resolving = [];

        $result = $resolver->resolve(
            'config',
            true,
            $definitions,
            $resolved,
            $resolving,
            [],
            $this->neverAutowire(...),
            $this->newStdClass(...),
        );

        self::assertSame('value', $result);
        self::assertSame('value', $resolved['config']);
    }

    public function testResolveFromFactoryUsesContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = [
            'object' => static function (ContainerInterface $container): stdClass {
                $container->has('object');

                return new stdClass();
            },
        ];
        $resolved = [];
        $resolving = [];

        $instance = $resolver->resolve(
            'object',
            false,
            $definitions,
            $resolved,
            $resolving,
            [],
            $this->neverAutowire(...),
            $this->newStdClass(...),
        );

        self::assertInstanceOf(stdClass::class, $instance);
        self::assertSame([], $resolved);
    }

    public function testResolveDoesNotCacheNullSingleton(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = [
            'nullable' => static function (ContainerInterface $container): mixed {
                $container->has('nullable');

                return null;
            },
        ];
        $resolved = [];
        $resolving = [];

        self::assertNull($resolver->resolve(
            'nullable',
            true,
            $definitions,
            $resolved,
            $resolving,
            [],
            $this->neverAutowire(...),
            $this->newStdClass(...),
        ));
        self::assertArrayNotHasKey('nullable', $resolved);
    }

    public function testResolveAutowiredCreatesInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = [];
        $resolved = [];
        $resolving = [];
        $created = new stdClass();

        $instance = $resolver->resolve(
            'autowired',
            true,
            $definitions,
            $resolved,
            $resolving,
            [],
            $this->alwaysAutowire(...),
            static fn (string $id): object => $id === 'autowired' ? $created : new stdClass(),
        );

        self::assertSame($created, $instance);
        self::assertSame($created, $resolved['autowired']);
        self::assertSame([], $resolving);
    }

    public function testResolveAutowiredThrowsOnCircularDependency(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = [];
        $resolved = [];
        $resolving = ['cyclic' => true];

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая зависимость');

        $resolver->resolve(
            'cyclic',
            true,
            $definitions,
            $resolved,
            $resolving,
            [],
            $this->alwaysAutowire(...),
            $this->newStdClass(...),
        );
    }

    public function testResolveAutowiredClearsResolvingStackAfterFailure(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = [];
        $resolved = [];
        $resolving = [];

        try {
            $resolver->resolve(
                'failing',
                true,
                $definitions,
                $resolved,
                $resolving,
                [],
                $this->alwaysAutowire(...),
                $this->throwOnInstantiate(...),
            );
            self::fail('Ожидалось исключение RuntimeException.');
        } catch (RuntimeException) {
            self::assertSame([], $resolving);
        }
    }

    public function testResolveThrowsNotFoundWhenServiceUnavailable(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = [];
        $resolved = [];
        $resolving = [];

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('не зарегистрирован');

        $resolver->resolve(
            'missing',
            true,
            $definitions,
            $resolved,
            $resolving,
            [],
            $this->neverAutowire(...),
            $this->newStdClass(...),
        );
    }

    public function testResolveAppliesDecorators(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $inner = new stdClass();
        $definitions = ['inner' => $inner];
        $resolved = [];
        $resolving = [];
        $decorations = 0;
        $decorators = [
            'inner' => [
                static function (mixed $service, ContainerInterface $container) use (&$decorations): mixed {
                    $container->has('inner');
                    ++$decorations;

                    return $service;
                },
            ],
        ];

        $result = $resolver->resolve(
            'inner',
            false,
            $definitions,
            $resolved,
            $resolving,
            $decorators,
            $this->neverAutowire(...),
            $this->newStdClass(...),
        );

        self::assertSame($inner, $result);
        self::assertSame(1, $decorations);
    }

    public function testResolveDetectsReentrantAutowiring(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $resolver = new ServiceInstanceResolver($container);
        $definitions = [];
        $resolved = [];
        $resolving = [];
        $decorators = [];
        $attempts = 0;

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая зависимость');

        $resolver->resolve(
            'svc',
            false,
            $definitions,
            $resolved,
            $resolving,
            $decorators,
            static fn (string $id): bool => $id === 'svc',
            function (string $id) use (
                $resolver,
                &$definitions,
                &$resolved,
                &$resolving,
                $decorators,
                &$attempts,
            ): object {
                ++$attempts;

                if ($attempts === 1) {
                    $nested = $resolver->resolve(
                        $id,
                        false,
                        $definitions,
                        $resolved,
                        $resolving,
                        $decorators,
                        static fn (string $serviceId): bool => $serviceId === 'svc',
                        static fn (): object => new stdClass(),
                    );

                    self::assertIsObject($nested);

                    return $nested;
                }

                return new stdClass();
            },
        );
    }

    public function testContainerDetectsAutowireCycleForInfection(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая зависимость');

        $container->get(CircularA::class);
    }

    private function neverAutowire(string $id): bool
    {
        return str_contains($id, "\0");
    }

    private function alwaysAutowire(string $id): bool
    {
        return $id !== '';
    }

    private function newStdClass(string $id): stdClass
    {
        if ($id === '') {
            throw new RuntimeException('Пустой идентификатор сервиса.');
        }

        return new stdClass();
    }

    private function throwOnInstantiate(string $id): object
    {
        throw new RuntimeException(\sprintf('instantiate failed for "%s"', $id));
    }
}
