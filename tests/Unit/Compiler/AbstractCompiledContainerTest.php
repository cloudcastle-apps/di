<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Compiled\StubCompiledContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractCompiledContainer::class)]
final class AbstractCompiledContainerTest extends TestCase
{
    public function testGetResolvesAliasAndCachesSingleton(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame('compiled-value', $container->get('value'));
        self::assertSame('compiled-value', $container->get('alias.id'));
        self::assertSame($container->get('value'), $container->get('value'));
    }

    public function testMakeCreatesNewInstancesWithoutCache(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame('compiled-value', $container->make('value'));
        self::assertSame('compiled-value', $container->make('value'));
    }

    public function testImmutableMutatorsThrow(): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(ContainerException::class);

        $container->set('x', 'y');
    }

    public function testGetTaggedReturnsServices(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame(['value' => 'compiled-value'], $container->getTagged('group'));
    }
}
