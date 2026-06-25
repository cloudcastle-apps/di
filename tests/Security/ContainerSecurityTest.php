<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Security;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Throwable;

/**
 * Проверки безопасного поведения контейнера при ошибках и нестандартных идентификаторах.
 */
#[CoversClass(Container::class)]
final class ContainerSecurityTest extends TestCase
{
    public function testMissingServiceDoesNotRegisterResolvedState(): void
    {
        $container = new Container();

        try {
            $container->get('missing');
            self::fail('Ожидалось исключение NotFoundException.');
        } catch (NotFoundException) {
            self::assertFalse($container->has('missing'));
            self::assertFalse($container->hasDefinition('missing'));
        }
    }

    public function testFactoryExceptionDoesNotCachePartialInstance(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('broken', static function () use (&$calls): never {
            ++$calls;
            throw new RuntimeException('сбой фабрики');
        });

        for ($attempt = 0; $attempt < 2; ++$attempt) {
            try {
                $container->get('broken');
                self::fail('Ожидалось исключение RuntimeException.');
            } catch (RuntimeException) {
                self::assertTrue($container->hasDefinition('broken'));
            }
        }

        self::assertSame(2, $calls);
    }

    public function testServiceIdentifierIsTreatedAsOpaqueString(): void
    {
        $container = new Container();
        $identifier = "service'; DROP TABLE users; --";
        $service = new stdClass();
        $container->set($identifier, $service);

        self::assertSame($service, $container->get($identifier));
    }

    public function testNotFoundMessageContainsOnlyRequestedIdentifier(): void
    {
        $container = new Container();
        $identifier = 'payments.gateway';

        try {
            $container->get($identifier);
            self::fail('Ожидалось исключение NotFoundException.');
        } catch (NotFoundException $notFoundException) {
            self::assertSame(
                \sprintf('Сервис "%s" не зарегистрирован.', $identifier),
                $notFoundException->getMessage(),
            );
            self::assertStringNotContainsString('vendor', $notFoundException->getMessage());
        }
    }

    public function testCircularDependencyAbortsResolution(): void
    {
        $container = new Container();
        $container->set('alpha', static fn (Container $container): mixed => $container->get('beta'));
        $container->set('beta', static fn (Container $container): mixed => $container->get('alpha'));

        try {
            $container->get('alpha');
            self::fail('Ожидалась ошибка при циклической зависимости.');
        } catch (Throwable $throwable) {
            self::assertNotInstanceOf(NotFoundException::class, $throwable);
        }
    }
}
