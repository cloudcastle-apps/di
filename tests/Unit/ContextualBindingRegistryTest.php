<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ContextualBinding;
use CloudCastle\DI\ContextualBindingRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContextualBindingRegistry::class)]
final class ContextualBindingRegistryTest extends TestCase
{
    public function testRegisterAndResolveReturnsLastMatchingGive(): void
    {
        $registry = new ContextualBindingRegistry();
        $registry->register(new ContextualBinding(
            consumerClass: 'App\\ReportService',
            need: \Psr\Log\LoggerInterface::class,
            give: 'log.file',
        ));
        $registry->register(new ContextualBinding(
            consumerClass: 'App\\ReportService',
            need: \Psr\Log\LoggerInterface::class,
            give: 'log.memory',
        ));

        self::assertSame('log.memory', $registry->resolve('App\\ReportService', \Psr\Log\LoggerInterface::class));
    }

    public function testResolveReturnsNullWhenNoRule(): void
    {
        $registry = new ContextualBindingRegistry();

        self::assertNull($registry->resolve('App\\Other', \Psr\Log\LoggerInterface::class));
    }

    public function testBindingsForReturnsRulesInRegistrationOrder(): void
    {
        $registry = new ContextualBindingRegistry();
        $first = new ContextualBinding('App\\A', 'Need\\One', 'give.one');
        $second = new ContextualBinding('App\\A', 'Need\\Two', 'give.two');
        $registry->register($first);
        $registry->register($second);

        self::assertSame([$first, $second], $registry->bindingsFor('App\\A'));
        self::assertSame([], $registry->bindingsFor('App\\Missing'));
    }
}
