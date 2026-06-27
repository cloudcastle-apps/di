<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\CompileConstructorPlanner;
use CloudCastle\DI\Compiler\CompileServiceKind;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerCompileException;
use CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker;
use CloudCastle\DI\Tests\Fixtures\Autowire\ChildSetterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerUser;
use CloudCastle\DI\Tests\Fixtures\Autowire\MethodInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\MethodParameterInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyInjectAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompileConstructorPlanner::class)]
final class CompileConstructorPlannerTest extends TestCase
{
    private CompileConstructorPlanner $planner;

    private Container $container;

    protected function setUp(): void
    {
        $this->planner = new CompileConstructorPlanner();
        $this->container = new Container();
    }

    public function testPlanBuildsAutowiredBinding(): void
    {
        $this->container->set(Clock::class, new Clock());

        $binding = $this->planner->plan($this->container, LoggerUser::class);

        self::assertSame(LoggerUser::class, $binding->id);
        self::assertSame(CompileServiceKind::Autowired, $binding->kind);
        self::assertSame(LoggerUser::class, $binding->className);
        self::assertCount(1, $binding->argumentExpressions);
    }

    public function testPlanWithoutConstructor(): void
    {
        $binding = $this->planner->plan($this->container, SimpleService::class);

        self::assertSame(SimpleService::class, $binding->id);
        self::assertSame([], $binding->argumentExpressions);
    }

    public function testPlanRejectsMissingClass(): void
    {
        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('не найден');

        $this->planner->plan($this->container, 'CloudCastle\\DI\\Tests\\Fixtures\\MissingClass');
    }

    public function testPlanRejectsNonInstantiableClass(): void
    {
        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('нельзя создать');

        $this->planner->plan($this->container, AbstractWorker::class);
    }

    public function testPlanRejectsPropertyInjection(): void
    {
        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('property injection');

        $this->planner->plan($this->container, PropertyInjectAttributeService::class);
    }

    public function testPlanRejectsMethodInjection(): void
    {
        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('method injection');

        $this->planner->plan($this->container, MethodInjectService::class);
    }

    public function testPlanIgnoresInheritedMethodsWithoutAttributes(): void
    {
        $this->container->set(Clock::class, new Clock());

        $binding = $this->planner->plan($this->container, ChildSetterService::class);

        self::assertSame(ChildSetterService::class, $binding->id);
        self::assertSame([], $binding->argumentExpressions);
    }

    public function testPlanRejectsMethodParameterInjection(): void
    {
        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('method injection');

        $this->planner->plan($this->container, MethodParameterInjectService::class);
    }
}
