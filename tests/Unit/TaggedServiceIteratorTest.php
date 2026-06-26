<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\TaggedServiceIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(TaggedServiceIterator::class)]
final class TaggedServiceIteratorTest extends TestCase
{
    public function testIteratorYieldsServicesInTagOrder(): void
    {
        $container = new Container();
        $first = new stdClass();
        $second = new stdClass();
        $container->set('a', $first);
        $container->set('b', $second);
        $container->tag('a', 'handlers');
        $container->tag('b', 'handlers');

        $values = iterator_to_array($container->getTaggedIterator('handlers'), false);

        self::assertSame([$first, $second], $values);
    }
}
