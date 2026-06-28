<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Contract;

use CloudCastle\DI\ContextualBinding;
use CloudCastle\DI\Contract\ContextualBindingConfiguratorInterface;
use CloudCastle\DI\Contract\ContextualBindingGiveInterface;
use CloudCastle\DI\Contract\ContextualBindingNeedsInterface;
use CloudCastle\DI\Contract\ContextualBindingRegistryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(ContextualBinding::class)]
final class ContextualBindingContractTest extends TestCase
{
    public function testContextualBindingValueObject(): void
    {
        $binding = new ContextualBinding(
            consumerClass: 'App\\ReportController',
            need: 'Psr\\Log\\LoggerInterface',
            give: 'App\\Log\\FileLogger',
        );

        self::assertSame('App\\ReportController', $binding->consumerClass);
        self::assertSame('Psr\\Log\\LoggerInterface', $binding->need);
        self::assertSame('App\\Log\\FileLogger', $binding->give);
    }

    public function testRegistryInterfaceDeclaresExpectedMethods(): void
    {
        $methods = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(ContextualBindingRegistryInterface::class))->getMethods(),
        );

        self::assertSame(['register', 'bindingsFor', 'resolve'], $methods);
    }

    public function testConfiguratorFluentInterfacesExist(): void
    {
        self::assertTrue((new ReflectionClass(ContextualBindingConfiguratorInterface::class))->isInterface());
        self::assertTrue((new ReflectionClass(ContextualBindingNeedsInterface::class))->isInterface());
        self::assertTrue((new ReflectionClass(ContextualBindingGiveInterface::class))->isInterface());

        self::assertSame(
            'when',
            (new ReflectionClass(ContextualBindingConfiguratorInterface::class))->getMethod('when')->getName(),
        );
        self::assertSame(
            ContextualBindingGiveInterface::class,
            (string) (new ReflectionClass(ContextualBindingNeedsInterface::class))
                ->getMethod('needs')
                ->getReturnType(),
        );
    }
}
