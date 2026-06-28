<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\CallableInvoker;
use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\Tests\Fixtures\Compiled\StubCompiledContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

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

    public function testGetThrowsWhenAliasTargetMissing(): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('alias.only');

        $container->get('alias.only');
    }

    public function testMakeThrowsWhenServiceMissing(): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(NotFoundException::class);

        $container->make('missing');
    }

    public function testMakeThrowsWhenAliasTargetMissing(): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('alias.only');

        $container->make('alias.only');
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
        self::assertArrayHasKey('value', $container->getTagged('group'));
        self::assertSame([], $container->getTagged('empty'));
    }

    public function testTaggedHelpersAndLazy(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame(['missing', 'value'], $container->getTaggedIds('group'));
        self::assertSame(
            ['compiled-value'],
            array_values(iterator_to_array($container->getTaggedIterator('group'))),
        );
        self::assertTrue($container->getTaggedLocator('group')->has('value'));
        self::assertSame('compiled-value', $container->lazy('value')->getValue());
    }

    public function testCallInvokesCallable(): void
    {
        $container = new StubCompiledContainer();

        self::assertSame('ok', $container->call(static fn (): string => 'ok'));
    }

    public function testIntrospectionMethods(): void
    {
        $container = new StubCompiledContainer();
        $container->get('value');

        self::assertSame(StubCompiledContainer::class, $container->getCompiledClassName());
        self::assertTrue($container->isFrozen());
        $container->freeze();
        self::assertSame(['null-value', 'value'], $container->getDefinitionIds());

        $dump = $container->dump();

        self::assertTrue($dump['frozen']);
        self::assertSame(['null-value', 'value'], $dump['definitions']);
        self::assertSame([], $dump['autowired']);
        self::assertSame(['alias.id' => 'value', 'alias.only' => 'missing'], $dump['aliases']);
        self::assertSame(['group' => ['missing', 'value'], 'empty' => []], $dump['tags']);
        self::assertSame([], $dump['decorators']);
        self::assertSame(['value'], $dump['resolved']);
        self::assertFalse($dump['autowiring']['enabled']);
        self::assertFalse($dump['autowiring']['parameterName']);
        self::assertFalse($dump['autowiring']['property']);
        self::assertFalse($dump['autowiring']['method']);
    }

    public function testCallReusesCallableInvoker(): void
    {
        $container = new StubCompiledContainer();
        $property = new ReflectionProperty(AbstractCompiledContainer::class, 'callableInvoker');

        $container->call(static fn (): string => 'first');
        $firstInvoker = $property->getValue($container);
        self::assertInstanceOf(CallableInvoker::class, $firstInvoker);

        $container->call(static fn (): string => 'second');
        $secondInvoker = $property->getValue($container);
        self::assertInstanceOf(CallableInvoker::class, $secondInvoker);

        self::assertSame($firstInvoker, $secondInvoker);
    }

    public function testAutowiringFlagsAreDisabled(): void
    {
        $container = new StubCompiledContainer();

        self::assertFalse($container->isAutowiringEnabled());
        self::assertFalse($container->isParameterNameAutowiringEnabled());
        self::assertFalse($container->isPropertyAutowiringEnabled());
        self::assertFalse($container->isMethodAutowiringEnabled());
    }

    public function testContextualGiveReturnsBakedRules(): void
    {
        $container = new StubCompiledContainer([
            'App\\Report' => [\Psr\Log\LoggerInterface::class => 'log.memory'],
        ]);

        self::assertSame('log.memory', $container->contextualGive('App\\Report', \Psr\Log\LoggerInterface::class));
        self::assertNull($container->contextualGive('App\\Other', \Psr\Log\LoggerInterface::class));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function immutableMutatorsProvider(): iterable
    {
        yield 'set' => ['set'];
        yield 'tag' => ['tag'];
        yield 'decorate' => ['decorate'];
        yield 'enableAutowiring' => ['enableAutowiring'];
        yield 'disableAutowiring' => ['disableAutowiring'];
        yield 'enableParameterNameAutowiring' => ['enableParameterNameAutowiring'];
        yield 'disableParameterNameAutowiring' => ['disableParameterNameAutowiring'];
        yield 'enablePropertyAutowiring' => ['enablePropertyAutowiring'];
        yield 'disablePropertyAutowiring' => ['disablePropertyAutowiring'];
        yield 'enableMethodAutowiring' => ['enableMethodAutowiring'];
        yield 'disableMethodAutowiring' => ['disableMethodAutowiring'];
        yield 'registerAttribute' => ['registerAttribute'];
        yield 'autowire' => ['autowire'];
        yield 'scan' => ['scan'];
        yield 'alias' => ['alias'];
        yield 'addDefinitions' => ['addDefinitions'];
        yield 'bind' => ['bind'];
        yield 'afterResolving' => ['afterResolving'];
    }

    #[DataProvider('immutableMutatorsProvider')]
    public function testImmutableMutatorsThrow(string $mutator): void
    {
        $container = new StubCompiledContainer();

        $this->expectException(ContainerException::class);

        match ($mutator) {
            'set' => $container->set('x', 'y'),
            'tag' => $container->tag('x', 'y'),
            'decorate' => $container->decorate('x', static function (): void {
            }),
            'enableAutowiring' => $container->enableAutowiring(),
            'disableAutowiring' => $container->disableAutowiring(),
            'enableParameterNameAutowiring' => $container->enableParameterNameAutowiring(),
            'disableParameterNameAutowiring' => $container->disableParameterNameAutowiring(),
            'enablePropertyAutowiring' => $container->enablePropertyAutowiring(),
            'disablePropertyAutowiring' => $container->disablePropertyAutowiring(),
            'enableMethodAutowiring' => $container->enableMethodAutowiring(),
            'disableMethodAutowiring' => $container->disableMethodAutowiring(),
            'registerAttribute' => $container->registerAttribute('Attr'),
            'autowire' => $container->autowire('Class'),
            'scan' => $container->scan('/tmp'),
            'alias' => $container->alias('a', 'b'),
            'addDefinitions' => $container->addDefinitions([]),
            'bind' => $container->bind('a', 'b'),
            'afterResolving' => $container->afterResolving('x', static function (): void {
            }),
            default => self::fail(\sprintf('Неизвестный mutator "%s".', $mutator)),
        };
    }
}
