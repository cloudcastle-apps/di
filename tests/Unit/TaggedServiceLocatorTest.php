<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\TaggedServiceLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(TaggedServiceLocator::class)]
#[CoversClass(Container::class)]
final class TaggedServiceLocatorTest extends TestCase
{
    public function testLocatorHasRequiresRegisteredDefinition(): void
    {
        $container = new Container();
        $container->tag('orphan', 'handlers');

        $locator = $container->getTaggedLocator('handlers');

        self::assertFalse($locator->has('orphan'));
    }

    public function testLocatorGetReturnsTaggedService(): void
    {
        $container = new Container();
        $service = new stdClass();
        $container->set('handler', $service);
        $container->tag('handler', 'handlers');

        $locator = $container->getTaggedLocator('handlers');

        self::assertTrue($locator->has('handler'));
        self::assertSame($service, $locator->get('handler'));
    }

    public function testLocatorGetThrowsForUnknownId(): void
    {
        $container = new Container();
        $locator = $container->getTaggedLocator('handlers');

        $this->expectException(NotFoundException::class);

        $locator->get('missing');
    }

    public function testGetTaggedIdsReturnsRegistrationOrderWithoutResolution(): void
    {
        $container = new Container();
        $container->set('a', static fn (): stdClass => new stdClass());
        $container->tag('a', 'handlers');
        $container->tag('b', 'handlers');

        self::assertSame(['a', 'b'], $container->getTaggedIds('handlers'));
    }

    public function testLocatorIteratesTaggedServices(): void
    {
        $container = new Container();
        $first = new stdClass();
        $second = new stdClass();
        $container->set('a', $first);
        $container->set('b', $second);
        $container->tag('a', 'handlers');
        $container->tag('b', 'handlers');

        $locator = $container->getTaggedLocator('handlers');

        self::assertSame(['a' => $first, 'b' => $second], iterator_to_array($locator));
    }
}
