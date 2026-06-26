<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerFreezeTest extends TestCase
{
    public function testFreezeIsIdempotent(): void
    {
        $container = new Container();

        self::assertFalse($container->isFrozen());

        $container->freeze();
        $container->freeze();

        self::assertTrue($container->isFrozen());
    }

    public function testGetWorksAfterFreeze(): void
    {
        $container = new Container();
        $service = new stdClass();
        $container->set('service', $service);
        $container->freeze();

        self::assertSame($service, $container->get('service'));
    }

    public function testMakeAndCallWorkAfterFreeze(): void
    {
        $container = new Container();
        $container->set('value', 42);
        $container->freeze();

        self::assertSame(42, $container->make('value'));
        self::assertSame(42, $container->call(static fn (): int => 42));
    }

    /**
     * @return iterable<string, list{callable(Container): void}>
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function mutationProvider(): iterable
    {
        yield 'set' => [static function (Container $container): void {
            $container->set('x', new stdClass());
        }];
        yield 'autowire' => [static function (Container $container): void {
            $container->autowire(stdClass::class);
        }];
        yield 'alias' => [static function (Container $container): void {
            $container->alias('a', 'b');
        }];
        yield 'tag' => [static function (Container $container): void {
            $container->tag('x', 't');
        }];
        yield 'decorate' => [static function (Container $container): void {
            $container->decorate('x', static fn (mixed $inner): mixed => $inner);
        }];
        yield 'bind' => [static function (Container $container): void {
            $container->bind(LoggerInterface::class, stdClass::class);
        }];
        yield 'addDefinitions' => [static function (Container $container): void {
            $container->addDefinitions(['x' => new stdClass()]);
        }];
        yield 'enableAutowiring' => [static function (Container $container): void {
            $container->enableAutowiring();
        }];
        yield 'disableAutowiring' => [static function (Container $container): void {
            $container->disableAutowiring();
        }];
        yield 'enableParameterNameAutowiring' => [static function (Container $container): void {
            $container->enableParameterNameAutowiring();
        }];
        yield 'afterResolving' => [static function (Container $container): void {
            $container->afterResolving('x', static function (): void {
            });
        }];
    }

    /**
     * @param callable(Container): void $mutate
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('mutationProvider')]
    public function testMutationThrowsWhenFrozen(callable $mutate): void
    {
        $container = new Container();
        $container->set('b', new stdClass());
        $container->freeze();

        $definitionIdsBefore = $container->getDefinitionIds();

        try {
            $mutate($container);
            self::fail('Ожидалось исключение ContainerException.');
        } catch (ContainerException $containerException) {
            self::assertSame(
                'Контейнер заморожен: изменение определений запрещено.',
                $containerException->getMessage(),
            );
            self::assertStringNotContainsString('vendor', $containerException->getMessage());
        }

        self::assertSame($definitionIdsBefore, $container->getDefinitionIds());
    }
}
