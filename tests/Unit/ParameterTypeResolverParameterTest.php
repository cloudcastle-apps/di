<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ParameterTypeResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntClockService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Разрешение параметров конструктора через {@see ParameterTypeResolver}.
 */
#[CoversClass(ParameterTypeResolver::class)]
final class ParameterTypeResolverParameterTest extends TestCase
{
    public function testResolveParameterUnionSkipsLeadingBuiltinType(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $resolver = new ParameterTypeResolver($container);
        $parameter = (new ReflectionClass(IntClockService::class))
            ->getConstructor()
            ?->getParameters()[0];

        self::assertNotNull($parameter);
        self::assertInstanceOf(Clock::class, $resolver->resolve($parameter));
    }

    public function testFilterObjectNamedTypesContinuesAfterLeadingBuiltinType(): void
    {
        $resolver = new ParameterTypeResolver(new Container());

        $builtinType = $this->createMock(ReflectionNamedType::class);
        $builtinType->method('isBuiltin')->willReturn(true);

        $clockType = $this->createMock(ReflectionNamedType::class);
        $clockType->method('isBuiltin')->willReturn(false);
        $clockType->method('getName')->willReturn(Clock::class);

        $method = new ReflectionMethod(ParameterTypeResolver::class, 'filterObjectNamedTypes');

        /** @var list<ReflectionNamedType> $namedTypes */
        $namedTypes = $method->invoke($resolver, [$builtinType, $clockType]);

        self::assertCount(1, $namedTypes);
        self::assertSame(Clock::class, $namedTypes[0]->getName());
    }

    public function testResolveUsesNullSafeDeclaringClassForClosureParameter(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());

        $closure = static function (Clock $clock): void {
            unset($clock);
        };
        $parameter = (new ReflectionFunction($closure))->getParameters()[0];

        $resolver = new ParameterTypeResolver($container);

        self::assertInstanceOf(Clock::class, $resolver->resolve($parameter));
    }
}
