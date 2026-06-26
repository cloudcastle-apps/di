<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerAddDefinitionsTest extends TestCase
{
    public function testAddDefinitionsRegistersMultipleServices(): void
    {
        $container = new Container();
        $first = new stdClass();

        $container->addDefinitions([
            'first' => $first,
            'second' => static fn (): stdClass => new stdClass(),
        ]);

        self::assertSame($first, $container->get('first'));
        self::assertInstanceOf(stdClass::class, $container->get('second'));
    }
}
