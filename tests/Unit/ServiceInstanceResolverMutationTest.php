<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\ServiceInstanceResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\CircularA;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ServiceInstanceResolver::class)]
final class ServiceInstanceResolverMutationTest extends TestCase
{
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
}
