<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\ServiceAliasResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ServiceAliasResolver::class)]
final class ServiceAliasResolverTest extends TestCase
{
    public function testResolveReturnsOriginalIdWithoutAlias(): void
    {
        $resolver = new ServiceAliasResolver();

        self::assertSame('service', $resolver->resolve('service'));
    }

    public function testResolveFollowsAliasChain(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('alias', 'target');

        self::assertSame('target', $resolver->resolve('alias'));
    }

    public function testResolveFollowsMultiStepAliasChain(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('first', 'second');
        $resolver->alias('second', 'final');

        self::assertSame('final', $resolver->resolve('first'));
    }

    public function testIsAliasReturnsTrueForRegisteredAlias(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('alias', 'target');

        self::assertTrue($resolver->isAlias('alias'));
        self::assertFalse($resolver->isAlias('target'));
    }

    public function testGetAliasesReturnsRegisteredMap(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('alias', 'target');
        $resolver->alias('second', 'third');

        self::assertSame(
            ['alias' => 'target', 'second' => 'third'],
            $resolver->getAliases(),
        );
    }

    public function testAliasThrowsOnDirectCycle(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('a', 'b');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая цепочка alias');

        $resolver->alias('b', 'a');
    }

    public function testAliasThrowsOnIndirectCycle(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('a', 'b');
        $resolver->alias('b', 'c');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая цепочка alias');

        $resolver->alias('c', 'a');
    }

    public function testResolveThrowsOnPreexistingCycle(): void
    {
        $resolver = new ServiceAliasResolver();
        $aliases = (new ReflectionClass(ServiceAliasResolver::class))->getProperty('aliases');
        $aliases->setValue($resolver, ['a' => 'b', 'b' => 'a']);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая цепочка alias');

        $resolver->resolve('a');
    }
}
