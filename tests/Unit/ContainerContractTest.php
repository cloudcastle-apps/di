<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Контракт и иерархия типов контейнера и исключений.
 */
#[CoversClass(Container::class)]
#[CoversClass(ContainerException::class)]
#[CoversClass(NotFoundException::class)]
final class ContainerContractTest extends TestCase
{
    public function testContainerImplementsExtendedContract(): void
    {
        $container = new Container();

        self::assertInstanceOf(ContainerInterface::class, $container);
        self::assertInstanceOf(PsrContainerInterface::class, $container);
    }

    public function testContainerIsFinal(): void
    {
        self::assertTrue((new ReflectionClass(Container::class))->isFinal());
    }

    public function testExceptionsExtendRuntimeException(): void
    {
        self::assertInstanceOf(RuntimeException::class, new ContainerException('ошибка'));
        self::assertInstanceOf(RuntimeException::class, new NotFoundException('не найден'));
    }

    public function testExceptionsAreFinal(): void
    {
        self::assertTrue((new ReflectionClass(ContainerException::class))->isFinal());
        self::assertTrue((new ReflectionClass(NotFoundException::class))->isFinal());
    }
}
