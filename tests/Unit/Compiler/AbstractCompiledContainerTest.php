<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\LazyService;
use CloudCastle\DI\TaggedServiceIterator;
use CloudCastle\DI\TaggedServiceLocator;
use CloudCastle\DI\Tests\Fixtures\Compiled\StubCompiledContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
        self::assertSame(1, $container->createCount('value'));
    }

    public function testNullInstanceIsNotCached(): void
    {
        $container = new StubCompiledContainer();

        self::assertNull($container->get('null-value'));
        self::assertNull($container->get('null-value'));
        self::assertSame(2, $container->createCount('null-value'));
    }

    public function testMakeCreatesNewInstancesWithoutCache(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame('compiled-value', $container->make('value'));
        self::assertSame('compiled-value', $container->make('value'));
        self::assertSame(2, $container->createCount('value'));
    }

    public function testHasDetectsAliasDefinitionAndResolvedServices(): void
    {
        $container = new StubCompiledContainer();

        self::assertTrue($container->has('alias.only'));
        self::assertTrue($container->has('value'));
        self::assertFalse($container->has('missing'));
    }

    public function testGetThrowsWhenServiceMissing(): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(NotFoundException::class);

        $container->get('missing');
    }

    public function testMakeThrowsWhenServiceMissing(): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(NotFoundException::class);

        $container->make('missing');
    }

    public function testHasDefinition(): void
    {
        $container = new StubCompiledContainer();

        self::assertTrue($container->hasDefinition('value'));
        self::assertTrue($container->hasDefinition('alias.id'));
        self::assertFalse($container->hasDefinition('missing'));
    }

    public function testGetTaggedReturnsServicesAndSkipsMissing(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame(['value' => 'compiled-value'], $container->getTagged('group'));
        self::assertSame([], $container->getTagged('empty'));
    }

    public function testTaggedHelpersAndLazy(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame(['value', 'missing'], $container->getTaggedIds('group'));
        self::assertInstanceOf(TaggedServiceIterator::class, $container->getTaggedIterator('group'));
        self::assertInstanceOf(TaggedServiceLocator::class, $container->getTaggedLocator('group'));
        self::assertInstanceOf(LazyService::class, $container->lazy('value'));
    }

    public function testCallInvokesCallable(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame('ok', $container->call(static fn (): string => 'ok'));
    }

    public function testIntrospectionMethods(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame(StubCompiledContainer::class, $container->getCompiledClassName());
        self::assertTrue($container->isFrozen());
        $container->freeze();
        self::assertSame(['null-value', 'value'], $container->getDefinitionIds());

        $dump = $container->dump();

        self::assertTrue($dump['frozen']);
        self::assertSame(['null-value', 'value'], $dump['definitions']);
        self::assertSame(['alias.id' => 'value', 'alias.only' => 'missing'], $dump['aliases']);
        self::assertFalse($dump['autowiring']['enabled']);
    }

    public function testAutowiringFlagsAreDisabled(): void
    {
        $container = new StubCompiledContainer();

        self::assertFalse($container->isAutowiringEnabled());
        self::assertFalse($container->isParameterNameAutowiringEnabled());
        self::assertFalse($container->isPropertyAutowiringEnabled());
        self::assertFalse($container->isMethodAutowiringEnabled());
    }

    /**
     * @return iterable<string, array{callable(StubCompiledContainer): void}>
     */
    public static function immutableMutatorsProvider(): iterable
    {
        yield 'set' => [static fn (StubCompiledContainer $container): mixed => $container->set('x', 'y')];
        yield 'tag' => [static fn (StubCompiledContainer $container): mixed => $container->tag('x', 'y')];
        yield 'decorate' => [static fn (StubCompiledContainer $container): mixed => $container->decorate('x', static fn (): null => null)];
        yield 'enableAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->enableAutowiring()];
        yield 'disableAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->disableAutowiring()];
        yield 'enableParameterNameAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->enableParameterNameAutowiring()];
        yield 'disableParameterNameAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->disableParameterNameAutowiring()];
        yield 'enablePropertyAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->enablePropertyAutowiring()];
        yield 'disablePropertyAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->disablePropertyAutowiring()];
        yield 'enableMethodAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->enableMethodAutowiring()];
        yield 'disableMethodAutowiring' => [static fn (StubCompiledContainer $container): mixed => $container->disableMethodAutowiring()];
        yield 'registerAttribute' => [static fn (StubCompiledContainer $container): mixed => $container->registerAttribute('Attr')];
        yield 'autowire' => [static fn (StubCompiledContainer $container): mixed => $container->autowire('Class')];
        yield 'scan' => [static fn (StubCompiledContainer $container): mixed => $container->scan('/tmp')];
        yield 'alias' => [static fn (StubCompiledContainer $container): mixed => $container->alias('a', 'b')];
        yield 'addDefinitions' => [static fn (StubCompiledContainer $container): mixed => $container->addDefinitions([])];
        yield 'bind' => [static fn (StubCompiledContainer $container): mixed => $container->bind('a', 'b')];
        yield 'afterResolving' => [static fn (StubCompiledContainer $container): mixed => $container->afterResolving('x', static function (): void {
        })];
    }

    #[DataProvider('immutableMutatorsProvider')]
    public function testImmutableMutatorsThrow(callable $mutator): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(ContainerException::class);

        $mutator($container);
    }
}
