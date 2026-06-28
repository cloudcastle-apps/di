<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ServiceAliasResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ServiceAliasResolver::class)]
final class ServiceAliasResolverMutationTest extends TestCase
{
    public function testResolveTracksVisitedAliases(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('a', 'b');
        $resolver->alias('b', 'target');

        self::assertSame('target', $resolver->resolve('a'));
    }

    public function testHasCycleDetectsIndirectLoop(): void
    {
        $resolver = new ServiceAliasResolver();
        $resolver->alias('x', 'y');
        $resolver->alias('y', 'z');

        $this->expectException(\CloudCastle\DI\Exception\ContainerException::class);

        $resolver->alias('z', 'x');
    }

    public function testResolveThrowsOnThreeStepAliasCycle(): void
    {
        $resolver = new ServiceAliasResolver();
        $aliases = (new ReflectionClass(ServiceAliasResolver::class))->getProperty('aliases');
        $aliases->setValue($resolver, ['first' => 'second', 'second' => 'third', 'third' => 'first']);

        $this->expectException(\CloudCastle\DI\Exception\ContainerException::class);
        $this->expectExceptionMessage('циклическая цепочка alias');

        $resolver->resolve('first');
    }
}
