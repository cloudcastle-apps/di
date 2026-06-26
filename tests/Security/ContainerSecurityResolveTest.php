<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Security;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\TaggedServiceLocator;
use CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker;
use CloudCastle\DI\Tests\Fixtures\Autowire\CircularA;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * Безопасность resolve: кэш при ошибках, autowiring, alias, сообщения исключений.
 */
#[CoversClass(Container::class)]
#[CoversClass(TaggedServiceLocator::class)]
final class ContainerSecurityResolveTest extends TestCase
{
    public function testAfterResolvingExceptionDoesNotCacheSingleton(): void
    {
        $container = new Container();
        $factoryCalls = 0;
        $hookCalls = 0;

        $container->set('hooked', static function () use (&$factoryCalls): stdClass {
            ++$factoryCalls;

            return new stdClass();
        });
        $container->afterResolving('hooked', static function () use (&$hookCalls): never {
            ++$hookCalls;
            throw new RuntimeException('сбой hook');
        });

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $container->get('hooked');
                self::fail('Ожидалось исключение RuntimeException.');
            } catch (RuntimeException $exception) {
                self::assertSame('сбой hook', $exception->getMessage());
            }
        }

        self::assertSame(2, $factoryCalls);
        self::assertSame(2, $hookCalls);
    }

    public function testDecoratorExceptionDoesNotCacheSingleton(): void
    {
        $container = new Container();
        $factoryCalls = 0;

        $container->set('decorated', static function () use (&$factoryCalls): stdClass {
            ++$factoryCalls;

            return new stdClass();
        });
        $container->decorate('decorated', static function (): never {
            throw new RuntimeException('сбой декоратора');
        });

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $container->get('decorated');
                self::fail('Ожидалось исключение RuntimeException.');
            } catch (RuntimeException $exception) {
                self::assertSame('сбой декоратора', $exception->getMessage());
            }
        }

        self::assertSame(2, $factoryCalls);
    }

    public function testAutowiringDisabledRejectsArbitraryClassName(): void
    {
        $container = new Container();

        self::assertFalse($container->has(stdClass::class));

        try {
            $container->get(stdClass::class);
            self::fail('Ожидалось исключение NotFoundException.');
        } catch (NotFoundException $notFoundException) {
            self::assertSame(
                \sprintf('Сервис "%s" не зарегистрирован.', stdClass::class),
                $notFoundException->getMessage(),
            );
        }
    }

    public function testAbstractClassCannotBeRegisteredViaAutowire(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('нельзя создать через autowiring');

        $container->autowire(AbstractWorker::class);
    }

    public function testInterfaceIsNotResolvedViaGlobalAutowiring(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        self::assertFalse($container->has(LoggerInterface::class));

        try {
            $container->get(LoggerInterface::class);
            self::fail('Ожидалось исключение NotFoundException.');
        } catch (NotFoundException $notFoundException) {
            self::assertSame(
                \sprintf('Сервис "%s" не зарегистрирован.', LoggerInterface::class),
                $notFoundException->getMessage(),
            );
        }
    }

    public function testCyclicDependencyMessageContainsOnlyServiceIdentifier(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        try {
            $container->get(CircularA::class);
            self::fail('Ожидалось исключение ContainerException.');
        } catch (ContainerException $containerException) {
            self::assertStringContainsString(CircularA::class, $containerException->getMessage());
            self::assertStringNotContainsString('vendor', $containerException->getMessage());
            self::assertStringNotContainsString('tests', $containerException->getMessage());
        }
    }

    public function testAliasCycleRegistrationIsRejectedWithoutCorruptingPriorAlias(): void
    {
        $container = new Container();
        $service = new stdClass();
        $container->set('target', $service);
        $container->alias('entry', 'target');

        try {
            $container->alias('target', 'entry');
            self::fail('Ожидалось исключение ContainerException.');
        } catch (ContainerException $containerException) {
            self::assertStringContainsString('target', $containerException->getMessage());
        }

        self::assertSame($service, $container->get('entry'));
    }

    public function testTaggedLocatorNotFoundMessageIsScopedToIdAndTag(): void
    {
        $container = new Container();
        $container->set('handler.a', new stdClass());
        $container->tag('handler.a', 'handlers');

        $locator = $container->getTaggedLocator('handlers');

        try {
            $locator->get('handler.missing');
            self::fail('Ожидалось исключение NotFoundException.');
        } catch (NotFoundException $notFoundException) {
            self::assertSame(
                'Сервис "handler.missing" не найден в теге "handlers".',
                $notFoundException->getMessage(),
            );
            self::assertStringNotContainsString('vendor', $notFoundException->getMessage());
        }
    }

    public function testNullByteInServiceIdentifierIsOpaque(): void
    {
        $container = new Container();
        $identifier = "service\0injection";
        $service = new stdClass();
        $container->set($identifier, $service);

        self::assertSame($service, $container->get($identifier));
    }

    public function testSetAfterFailedFactoryAllowsRecovery(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('recoverable', static function () use (&$calls): never {
            ++$calls;
            throw new RuntimeException('временный сбой');
        });

        try {
            $container->get('recoverable');
            self::fail('Ожидалось исключение RuntimeException.');
        } catch (RuntimeException $runtimeException) {
            self::assertSame('временный сбой', $runtimeException->getMessage());
        }

        $replacement = new stdClass();
        $container->set('recoverable', $replacement);

        self::assertSame($replacement, $container->get('recoverable'));
        self::assertSame(1, $calls);
    }
}
