<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\CompileParameterReferenceResolver;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerCompileException;
use CloudCastle\DI\Tests\Fixtures\Autowire\BuiltinParameterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\BuiltinUnionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ContainerConsumer;
use CloudCastle\DI\Tests\Fixtures\Autowire\DualTypeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\InjectAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntClockOnlyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\LegacyUntypedService;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerOrClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerUser;
use CloudCastle\DI\Tests\Fixtures\Autowire\NamedLoggerConsumer;
use CloudCastle\DI\Tests\Fixtures\Autowire\OptionalDependency;
use CloudCastle\DI\Tests\Fixtures\Autowire\PsrContainerConsumer;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StringOrNullService;
use CloudCastle\DI\Tests\Fixtures\Autowire\UnionParameterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\UntypedParameterService;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\MemoryLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionParameter;

#[CoversClass(CompileParameterReferenceResolver::class)]
final class CompileParameterReferenceResolverTest extends TestCase
{
    private CompileParameterReferenceResolver $resolver;

    private Container $container;

    protected function setUp(): void
    {
        $this->resolver = new CompileParameterReferenceResolver();
        $this->container = new Container();
    }

    public function testResolvesInjectAttribute(): void
    {
        $this->container->set('app.clock', new Clock());
        $parameter = $this->constructorParameter(InjectAttributeService::class, 'clock');

        self::assertSame(
            '$this->get(\'app.clock\')',
            $this->resolver->resolveExpression($this->container, $parameter),
        );
    }

    public function testResolvesParameterNameWhenEnabled(): void
    {
        $this->container->enableParameterNameAutowiring();
        $this->container->set('logger', new FileLogger());

        $parameter = $this->constructorParameter(NamedLoggerConsumer::class, 'logger');

        self::assertSame(
            '$this->get(\'logger\')',
            $this->resolver->resolveExpression($this->container, $parameter),
        );
    }

    public function testResolvesByTypeWhenParameterNameAutowiringDisabled(): void
    {
        $this->container->set(FileLogger::class, new FileLogger());
        $this->container->alias(LoggerInterface::class, FileLogger::class);

        $parameter = $this->constructorParameter(NamedLoggerConsumer::class, 'logger');

        self::assertSame(
            '$this->get(' . var_export(LoggerInterface::class, true) . ')',
            $this->resolver->resolveExpression($this->container, $parameter),
        );
    }

    public function testResolvesContainerInterfaceAsThis(): void
    {
        $parameter = $this->constructorParameter(ContainerConsumer::class, 'container');

        self::assertSame('$this', $this->resolver->resolveExpression($this->container, $parameter));
    }

    public function testResolvesNamedTypeFromContainer(): void
    {
        $this->container->set(Clock::class, new Clock());
        $parameter = $this->constructorParameter(LoggerUser::class, 'clock');

        self::assertSame(
            '$this->get(' . var_export(Clock::class, true) . ')',
            $this->resolver->resolveExpression($this->container, $parameter),
        );
    }

    public function testResolvesContextualGiveForConsumer(): void
    {
        $this->container->set('memory.logger', new MemoryLogger());
        $this->container->set('default.logger', new FileLogger());
        $this->container->bind(LoggerInterface::class, 'default.logger');
        $this->container->when(ReportService::class)
            ->needs(LoggerInterface::class)
            ->give('memory.logger');

        $parameter = $this->constructorParameter(ReportService::class, 'logger');

        self::assertSame(
            '$this->get(\'memory.logger\')',
            $this->resolver->resolveExpression($this->container, $parameter),
        );
    }

    public function testThrowsWhenContextualGiveTargetMissing(): void
    {
        $this->container->when(ReportService::class)
            ->needs(LoggerInterface::class)
            ->give('missing.logger');

        $parameter = $this->constructorParameter(ReportService::class, 'logger');

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('Contextual give "missing.logger"');

        $this->resolver->resolveExpression($this->container, $parameter);
    }

    public function testResolvesPsrContainerInterfaceAsThis(): void
    {
        $parameter = $this->constructorParameter(PsrContainerConsumer::class, 'container');

        self::assertSame('$this', $this->resolver->resolveExpression($this->container, $parameter));
    }

    public function testResolvesBuiltinUnionDefault(): void
    {
        $parameter = $this->constructorParameter(BuiltinUnionService::class, 'code');

        self::assertSame("'200'", $this->resolver->resolveExpression($this->container, $parameter));
    }

    public function testResolvesLegacyUntypedDefault(): void
    {
        $parameter = $this->constructorParameter(LegacyUntypedService::class, 'value');

        self::assertSame("'legacy'", $this->resolver->resolveExpression($this->container, $parameter));
    }

    public function testResolvesOptionalDependencyDefault(): void
    {
        $parameter = $this->constructorParameter(OptionalDependency::class, 'clock');

        self::assertSame('NULL', $this->resolver->resolveExpression($this->container, $parameter));
    }

    public function testResolvesBuiltinDefault(): void
    {
        $parameter = $this->constructorParameter(BuiltinParameterService::class, 'label');

        self::assertSame("'default'", $this->resolver->resolveExpression($this->container, $parameter));
    }

    public function testResolvesUnionWhenServiceRegistered(): void
    {
        $this->container->set(Clock::class, new Clock());
        $parameter = $this->constructorParameter(LoggerOrClockService::class, 'dependency');

        self::assertSame(
            '$this->get(' . var_export(Clock::class, true) . ')',
            $this->resolver->resolveExpression($this->container, $parameter),
        );
    }

    public function testResolvesUnionWithDefaultWhenNothingRegistered(): void
    {
        $parameter = $this->constructorParameter(StringOrNullService::class, 'label');

        self::assertSame("'default'", $this->resolver->resolveExpression($this->container, $parameter));
    }

    public function testThrowsWhenNullableUnionHasNoDefault(): void
    {
        $parameter = $this->constructorParameter(UnionParameterService::class, 'clock');

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('Не удалось разрешить параметр $clock');

        $this->resolver->resolveExpression($this->container, $parameter);
    }

    public function testWrapsUnionResolverFailure(): void
    {
        $parameter = $this->constructorParameter(DualTypeService::class, 'value');

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('union-параметр $value');

        $this->resolver->resolveExpression($this->container, $parameter);
    }

    public function testThrowsWhenUnionHasOnlyMissingObjectMembers(): void
    {
        $parameter = $this->constructorParameter(IntClockOnlyService::class, 'dependency');

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('Не удалось разрешить параметр $dependency');

        $this->resolver->resolveExpression($this->container, $parameter);
    }

    public function testThrowsWhenRequiredTypeMissing(): void
    {
        $parameter = $this->constructorParameter(RequiredClockService::class, 'clock');

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('Не удалось разрешить параметр $clock');

        $this->resolver->resolveExpression($this->container, $parameter);
    }

    public function testThrowsWhenUntypedParameterHasNoDefault(): void
    {
        $parameter = $this->constructorParameter(UntypedParameterService::class, 'value');

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('Не удалось разрешить параметр $value');

        $this->resolver->resolveExpression($this->container, $parameter);
    }

    /**
     * @param class-string $className
     */
    private function constructorParameter(string $className, string $parameterName): ReflectionParameter
    {
        $constructor = (new ReflectionClass($className))->getConstructor();
        self::assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getName() === $parameterName) {
                return $parameter;
            }
        }

        self::fail(\sprintf('Параметр $%s не найден в %s.', $parameterName, $className));
    }
}
