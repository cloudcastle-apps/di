<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

/**
 * Tagged services и декораторы контейнера.
 */
#[CoversClass(Container::class)]
final class ContainerTaggedAndDecoratorTest extends TestCase
{
    public function testGetTaggedReturnsServicesInRegistrationOrder(): void
    {
        $container = new Container();
        $first = new stdClass();
        $second = new stdClass();
        $container->set('alpha', $first);
        $container->set('beta', $second);
        $container->tag('alpha', 'handlers');
        $container->tag('beta', 'handlers');

        self::assertSame(
            ['alpha' => $first, 'beta' => $second],
            $container->getTagged('handlers'),
        );
    }

    public function testGetTaggedReturnsEmptyArrayForUnknownTag(): void
    {
        $container = new Container();

        self::assertSame([], $container->getTagged('missing'));
    }

    public function testGetTaggedIdsReturnsEmptyForUnknownTag(): void
    {
        $container = new Container();

        self::assertSame([], $container->getTaggedIds('missing'));
    }

    public function testTagDoesNotDuplicateServiceId(): void
    {
        $container = new Container();
        $service = new stdClass();
        $container->set('handler', $service);
        $container->tag('handler', 'handlers');
        $container->tag('handler', 'handlers');

        self::assertSame(['handler' => $service], $container->getTagged('handlers'));
    }

    public function testGetTaggedSkipsUndefinedServices(): void
    {
        $container = new Container();
        $service = new stdClass();
        $container->set('present', $service);
        $container->tag('absent', 'group');
        $container->tag('present', 'group');

        self::assertSame(['present' => $service], $container->getTagged('group'));
    }

    public function testGetTaggedResolvesFactoriesOnce(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('handler', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->tag('handler', 'handlers');

        $tagged = $container->getTagged('handlers');
        $container->getTagged('handlers');

        self::assertCount(1, $tagged);
        self::assertSame(1, $calls);
    }

    public function testDecorateWrapsResolvedService(): void
    {
        $container = new Container();
        $inner = new stdClass();
        $container->set('service', $inner);
        $container->decorate(
            'service',
            static fn (mixed $instance, ContainerInterface $container): array => [
                'inner' => $instance,
                'container' => $container,
            ],
        );

        $decorated = $container->get('service');

        self::assertIsArray($decorated);
        self::assertSame($inner, $decorated['inner']);
        self::assertSame($container, $decorated['container']);
    }

    public function testDecoratorsApplyInRegistrationOrder(): void
    {
        $container = new Container();
        $container->set('service', static fn (): string => 'core');
        $container->decorate(
            'service',
            static function (mixed $inner): string {
                if (!\is_string($inner)) {
                    throw new UnexpectedValueException('Expected string.');
                }

                return $inner . '+first';
            },
        );
        $container->decorate(
            'service',
            static function (mixed $inner): string {
                if (!\is_string($inner)) {
                    throw new UnexpectedValueException('Expected string.');
                }

                return $inner . '+second';
            },
        );

        self::assertSame('core+first+second', $container->get('service'));
    }

    public function testDecorateUsesCachedInstanceWithoutReapplyingInnerFactory(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('service', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->decorate(
            'service',
            static fn (mixed $inner): object => (object) ['inner' => $inner],
        );

        $container->get('service');

        self::assertSame(1, $calls);
        self::assertSame($container->get('service'), $container->get('service'));
    }

    public function testSetClearsDecoratedSingleton(): void
    {
        $container = new Container();
        $container->set('service', static fn (): string => 'first');
        $container->decorate(
            'service',
            static function (mixed $inner): string {
                if (!\is_string($inner)) {
                    throw new UnexpectedValueException('Expected string.');
                }

                return 'decorated:' . $inner;
            },
        );
        self::assertSame('decorated:first', $container->get('service'));

        $container->set('service', static fn (): string => 'second');

        self::assertSame('decorated:second', $container->get('service'));
    }
}
