<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Load;

use CloudCastle\DI\Container;
use CloudCastle\DI\TaggedServiceIterator;
use CloudCastle\DI\TaggedServiceLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Нагрузочные сценарии tagged services: ids, iterator, locator.
 */
#[CoversClass(Container::class)]
#[CoversClass(TaggedServiceIterator::class)]
#[CoversClass(TaggedServiceLocator::class)]
final class ContainerTaggedLoadTest extends TestCase
{
    private const int TAGGED_COUNT = 1000;

    private const float TIME_BUDGET_SECONDS = 2.5;

    public function testGetTaggedIdsReturnsManyIdsWithoutResolution(): void
    {
        $container = new Container();

        for ($index = 0; $index < self::TAGGED_COUNT; ++$index) {
            $container->set('handler.' . $index, new stdClass());
            $container->tag('handler.' . $index, 'handlers');
        }

        $ids = $container->getTaggedIds('handlers');

        self::assertCount(self::TAGGED_COUNT, $ids);

        for ($index = 0; $index < self::TAGGED_COUNT; ++$index) {
            self::assertTrue(\in_array('handler.' . $index, $ids, true));
        }
    }

    public function testGetTaggedIteratorResolvesManyHandlers(): void
    {
        $container = new Container();
        $resolved = 0;

        for ($index = 0; $index < self::TAGGED_COUNT; ++$index) {
            $container->set('handler.' . $index, new stdClass());
            $container->tag('handler.' . $index, 'handlers');
        }

        foreach ($container->getTaggedIterator('handlers') as $handler) {
            self::assertInstanceOf(stdClass::class, $handler);
            ++$resolved;
        }

        self::assertSame(self::TAGGED_COUNT, $resolved);
    }

    public function testGetTaggedLocatorHasAndGetMany(): void
    {
        $container = new Container();

        for ($index = 0; $index < self::TAGGED_COUNT; ++$index) {
            $container->set('handler.' . $index, new stdClass());
            $container->tag('handler.' . $index, 'handlers');
        }

        $locator = $container->getTaggedLocator('handlers');

        for ($index = 0; $index < self::TAGGED_COUNT; ++$index) {
            $serviceId = 'handler.' . $index;
            self::assertTrue($locator->has($serviceId));
            self::assertInstanceOf(stdClass::class, $locator->get($serviceId));
        }
    }

    public function testTaggedBulkOperationsWithinTimeBudget(): void
    {
        $container = new Container();

        for ($index = 0; $index < self::TAGGED_COUNT; ++$index) {
            $container->set('tagged.' . $index, static fn (): int => $index);
            $container->tag('tagged.' . $index, 'pipeline');
        }

        $startedAt = microtime(true);

        self::assertCount(self::TAGGED_COUNT, $container->getTaggedIds('pipeline'));

        $sum = 0;

        foreach ($container->getTaggedIterator('pipeline') as $value) {
            self::assertIsInt($value);
            $sum += $value;
        }

        self::assertSame(array_sum(range(0, self::TAGGED_COUNT - 1)), $sum);
        self::assertLessThan(self::TIME_BUDGET_SECONDS, microtime(true) - $startedAt);
    }
}
