<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Contract;

use CloudCastle\DI\Compiler\ContainerCompileResult;
use CloudCastle\DI\Contract\CompiledContainerInterface;
use CloudCastle\DI\Contract\ContainerCompilerInterface;
use CloudCastle\DI\Contract\ContainerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class ContainerCompilerContractTest extends TestCase
{
    public function testCompiledContainerExtendsContainerInterface(): void
    {
        self::assertTrue((new ReflectionClass(CompiledContainerInterface::class))->isInterface());

        $implements = class_implements(CompiledContainerInterface::class);

        self::assertIsArray($implements);
        self::assertContains(ContainerInterface::class, $implements);
    }

    public function testCompiledContainerDeclaresCompiledClassNameAccessor(): void
    {
        $method = (new ReflectionClass(CompiledContainerInterface::class))->getMethod('getCompiledClassName');

        self::assertTrue($method->isPublic());
        self::assertSame('string', (string) $method->getReturnType());
    }

    public function testCompilerInterfaceDeclaresCompileMethod(): void
    {
        $method = (new ReflectionClass(ContainerCompilerInterface::class))->getMethod('compile');

        self::assertTrue($method->isPublic());
        self::assertSame(
            ContainerCompileResult::class,
            (string) $method->getReturnType(),
        );

        $parameters = $method->getParameters();

        self::assertSame('container', $parameters[0]->getName());
        self::assertSame(ContainerInterface::class, (string) $parameters[0]->getType());
        self::assertSame('outputPath', $parameters[1]->getName());
        self::assertSame('string', (string) $parameters[1]->getType());
        self::assertSame('className', $parameters[2]->getName());
        self::assertTrue($parameters[2]->allowsNull());
    }

    public function testCompileMethodHasExpectedParameterCount(): void
    {
        $method = new ReflectionMethod(ContainerCompilerInterface::class, 'compile');

        self::assertCount(3, $method->getParameters());
    }
}
