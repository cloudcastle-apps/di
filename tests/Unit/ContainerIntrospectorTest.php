<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ContainerIntrospector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ContainerIntrospector::class)]
final class ContainerIntrospectorTest extends TestCase
{
    public function testDefinitionIdsEmpty(): void
    {
        $introspector = new ContainerIntrospector(
            frozen: false,
            definitions: [],
            autowired: [],
            aliases: [],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );

        self::assertSame([], $introspector->definitionIds());
    }

    public function testDumpEmpty(): void
    {
        $introspector = new ContainerIntrospector(
            frozen: false,
            definitions: [],
            autowired: [],
            aliases: [],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );

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
            $introspector->dump(),
        );
    }

    public function testDefinitionIdsSortedAndUnique(): void
    {
        $introspector = new ContainerIntrospector(
            frozen: false,
            definitions: ['zebra' => new stdClass(), 'alpha' => new stdClass()],
            autowired: [stdClass::class => true],
            aliases: ['alias.target' => 'alpha'],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );

        self::assertSame(
            ['alias.target', 'alpha', stdClass::class, 'zebra'],
            $introspector->definitionIds(),
        );
    }

    public function testDumpSnapshotSorted(): void
    {
        $introspector = new ContainerIntrospector(
            frozen: true,
            definitions: ['z' => new stdClass(), 'a' => new stdClass()],
            autowired: [],
            aliases: [],
            tags: ['t' => ['z']],
            decorators: ['z' => [static fn (mixed $i): mixed => $i]],
            resolved: ['z' => new stdClass()],
            autowiringFlags: [
                'enabled' => true,
                'parameterName' => false,
                'property' => false,
                'method' => true,
            ],
        );

        self::assertSame(
            [
                'frozen' => true,
                'definitions' => ['a', 'z'],
                'autowired' => [],
                'aliases' => [],
                'tags' => ['t' => ['z']],
                'decorators' => ['z'],
                'resolved' => ['z'],
                'autowiring' => [
                    'enabled' => true,
                    'parameterName' => false,
                    'property' => false,
                    'method' => true,
                ],
            ],
            $introspector->dump(),
        );
    }

    public function testDefinitionIdsFromEachSourceIndependently(): void
    {
        $definitionsOnly = new ContainerIntrospector(
            frozen: false,
            definitions: ['from.def' => new stdClass()],
            autowired: [],
            aliases: [],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );
        $autowiredOnly = new ContainerIntrospector(
            frozen: false,
            definitions: [],
            autowired: [stdClass::class => true],
            aliases: [],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );
        $aliasesOnly = new ContainerIntrospector(
            frozen: false,
            definitions: [],
            autowired: [],
            aliases: ['from.alias' => 'target'],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );

        self::assertSame(['from.def'], $definitionsOnly->definitionIds());
        self::assertSame([stdClass::class], $autowiredOnly->definitionIds());
        self::assertSame(['from.alias'], $aliasesOnly->definitionIds());
    }

    public function testDefinitionIdsReturnsListWithSequentialKeys(): void
    {
        $introspector = new ContainerIntrospector(
            frozen: false,
            definitions: ['a' => new stdClass(), 'b' => new stdClass()],
            autowired: [],
            aliases: [],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );
        $ids = $introspector->definitionIds();

        self::assertSame([0, 1], array_keys($ids));
    }

    public function testDumpSortsAllKeyLists(): void
    {
        $introspector = new ContainerIntrospector(
            frozen: false,
            definitions: ['m' => new stdClass(), 'a' => new stdClass()],
            autowired: ['Z\\Z' => true, 'A\\A' => true],
            aliases: [],
            tags: [],
            decorators: ['b' => [static fn (mixed $i): mixed => $i], 'a' => [static fn (mixed $i): mixed => $i]],
            resolved: ['z' => new stdClass(), 'a' => new stdClass()],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );

        $dump = $introspector->dump();

        self::assertSame(['a', 'm'], $dump['definitions']);
        self::assertSame(['A\\A', 'Z\\Z'], $dump['autowired']);
        self::assertSame(['a', 'b'], $dump['decorators']);
        self::assertSame(['a', 'z'], $dump['resolved']);
    }

    public function testDefinitionIdsDeduplicatesOverlappingKeys(): void
    {
        $introspector = new ContainerIntrospector(
            frozen: false,
            definitions: [stdClass::class => new stdClass()],
            autowired: [stdClass::class => true],
            aliases: ['alias.std' => stdClass::class],
            tags: [],
            decorators: [],
            resolved: [],
            autowiringFlags: [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        );

        self::assertSame(['alias.std', stdClass::class], $introspector->definitionIds());
    }
}
