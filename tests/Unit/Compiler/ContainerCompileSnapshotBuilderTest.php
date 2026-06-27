<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\CompileServiceKind;
use CloudCastle\DI\Compiler\ContainerCompileSnapshotBuilder;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerCompileException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerUser;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerCompileSnapshotBuilder::class)]
final class ContainerCompileSnapshotBuilderTest extends TestCase
{
    private ContainerCompileSnapshotBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ContainerCompileSnapshotBuilder();
    }

    public function testBuildCollectsBindings(): void
    {
        $container = new Container();
        $container->set('label', 'compiled');
        $container->set(Clock::class, new Clock());
        $container->autowire(SimpleService::class);
        $container->alias('clock.alias', Clock::class);
        $container->tag(Clock::class, 'time');
        $container->freeze();

        $snapshot = $this->builder->build($container);

        self::assertSame(['clock.alias' => Clock::class], $snapshot->aliases);
        self::assertSame(['time' => [Clock::class]], $snapshot->tags);
        self::assertCount(3, $snapshot->bindings);

        $literal = $this->findBinding($snapshot->bindings, 'label');
        self::assertSame(CompileServiceKind::Literal, $literal->kind);
        self::assertSame('compiled', $literal->literalValue);

        $instance = $this->findBinding($snapshot->bindings, Clock::class);
        self::assertSame(CompileServiceKind::NewInstance, $instance->kind);
        self::assertSame(Clock::class, $instance->className);

        $autowired = $this->findBinding($snapshot->bindings, SimpleService::class);
        self::assertSame(CompileServiceKind::Autowired, $autowired->kind);
    }

    public function testBuildRejectsUnfrozenContainer(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('замороженного');

        $this->builder->build($container);
    }

    public function testBuildRejectsDecorators(): void
    {
        $container = new Container();
        $container->set('inner', new Clock());
        $container->decorate('inner', static fn (mixed $inner): mixed => $inner);
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('декораторы');

        $this->builder->build($container);
    }

    public function testBuildRejectsPropertyAutowiring(): void
    {
        $container = new Container();
        $container->enablePropertyAutowiring();
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('constructor autowiring');

        $this->builder->build($container);
    }

    public function testBuildRejectsMethodAutowiring(): void
    {
        $container = new Container();
        $container->enableMethodAutowiring();
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('constructor autowiring');

        $this->builder->build($container);
    }

    public function testBuildRejectsPrebuiltObjectWithConstructorParameters(): void
    {
        $container = new Container();
        $container->set('user', new LoggerUser(new Clock()));
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('готовый экземпляр');

        $this->builder->build($container);
    }

    /**
     * @param list<\CloudCastle\DI\Compiler\CompileServiceBinding> $bindings
     */
    private function findBinding(array $bindings, string $id): \CloudCastle\DI\Compiler\CompileServiceBinding
    {
        foreach ($bindings as $binding) {
            if ($binding->id === $id) {
                return $binding;
            }
        }

        self::fail(\sprintf('Binding "%s" не найден.', $id));
    }
}
