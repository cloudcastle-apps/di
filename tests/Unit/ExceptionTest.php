<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;

#[CoversClass(ContainerException::class)]
#[CoversClass(NotFoundException::class)]
final class ExceptionTest extends TestCase
{
    public function testContainerExceptionStoresMessage(): void
    {
        $exception = new ContainerException('ошибка контейнера');

        self::assertSame('ошибка контейнера', $exception->getMessage());
    }

    public function testContainerExceptionIsAcceptedAsPsrInterface(): void
    {
        $this->assertContainerException(new ContainerException('ошибка контейнера'));
    }

    public function testNotFoundExceptionStoresMessage(): void
    {
        $exception = new NotFoundException('сервис не найден');

        self::assertSame('сервис не найден', $exception->getMessage());
    }

    public function testNotFoundExceptionIsAcceptedAsPsrInterface(): void
    {
        $this->assertNotFoundException(new NotFoundException('сервис не найден'));
    }

    public function testExceptionsAreFinal(): void
    {
        self::assertTrue((new ReflectionClass(ContainerException::class))->isFinal());
        self::assertTrue((new ReflectionClass(NotFoundException::class))->isFinal());
    }

    private function assertContainerException(ContainerExceptionInterface $exception): void
    {
        self::assertSame('ошибка контейнера', $exception->getMessage());
    }

    private function assertNotFoundException(NotFoundExceptionInterface $exception): void
    {
        self::assertSame('сервис не найден', $exception->getMessage());
    }
}
