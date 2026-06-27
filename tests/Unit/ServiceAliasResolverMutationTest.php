<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ServiceAliasResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
}
