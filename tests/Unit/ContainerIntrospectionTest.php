<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerIntrospectionTest extends TestCase
{
    public function testGetDefinitionIdsReturnsEmptyListForNewContainer(): void
    {
        self::assertSame([], (new Container())->getDefinitionIds());
    }

    public function testDumpReturnsEmptySnapshotForNewContainer(): void
    {
        self::assertSame(
            [
                'frozen' => false,
                'definitions' => [],
                'autowired' => [],
                'aliases' => [],
                'tags' => [],
                'decorators' => [],
                'resolved' => [],
                'autowiring' => [
                    'enabled' => false,
                    'parameterName' => false,
                    'property' => false,
                    'method' => false,
                ],
            ],
            (new Container())->dump(),
        );
    }

    public function testGetDefinitionIdsReturnsSortedUniqueIds(): void
    {
        $container = new Container();
        $container->set('zebra', new stdClass());
        $container->set('alpha', new stdClass());
        $container->autowire(stdClass::class);
        $container->alias('alias.target', 'alpha');

        self::assertSame(
            ['alias.target', 'alpha', stdClass::class, 'zebra'],
            $container->getDefinitionIds(),
        );
    }

    public function testDumpReturnsStructuredSnapshotWithoutResolvingServices(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('lazy.factory', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->tag('lazy.factory', 'handlers');
        $container->decorate('lazy.factory', static fn (mixed $inner): mixed => $inner);
        $container->enableAutowiring();
        $container->freeze();

        $dump = $container->dump();

        self::assertTrue($dump['frozen']);
        self::assertSame(['lazy.factory'], $dump['definitions']);
        self::assertSame([], $dump['autowired']);
        self::assertSame([], $dump['aliases']);
        self::assertSame(['handlers' => ['lazy.factory']], $dump['tags']);
        self::assertSame(['lazy.factory'], $dump['decorators']);
        self::assertSame([], $dump['resolved']);
        self::assertTrue($dump['autowiring']['enabled']);
        self::assertFalse($dump['autowiring']['parameterName']);
    }

    public function testGetDefinitionIdsDeduplicatesOverlappingSources(): void
    {
        $container = new Container();
        $container->set(stdClass::class, new stdClass());
        $container->autowire(stdClass::class);
        $container->alias('alias.std', stdClass::class);

        self::assertSame(['alias.std', stdClass::class], $container->getDefinitionIds());
    }

    public function testDumpIncludesResolvedIdsAfterGet(): void
    {
        $container = new Container();
        $container->set('cached', new stdClass());
        $container->get('cached');

        self::assertSame(['cached'], $container->dump()['resolved']);
    }

    public function testDumpReflectsAutowiringFlagsAndAliases(): void
    {
        $container = new Container();
        $container->set('target', new stdClass());
        $container->alias('aliased', 'target');
        $container->enableAutowiring();
        $container->enableMethodAutowiring();

        self::assertSame(
            [
                'enabled' => true,
                'parameterName' => false,
                'property' => false,
                'method' => true,
            ],
            $container->dump()['autowiring'],
        );
        self::assertSame(['aliased' => 'target'], $container->dump()['aliases']);
    }

    public function testFreezeBlocksScan(): void
    {
        $container = new Container();
        $container->freeze();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('заморожен');

        $container->scan(__DIR__ . '/../../src', 'CloudCastle\\DI\\');
    }
}
