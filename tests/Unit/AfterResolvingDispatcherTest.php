<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\AfterResolvingDispatcher;
use CloudCastle\DI\Contract\ContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(AfterResolvingDispatcher::class)]
final class AfterResolvingDispatcherTest extends TestCase
{
    public function testDispatchInvokesRegisteredCallbacks(): void
    {
        $dispatcher = new AfterResolvingDispatcher();
        $calls = 0;
        $container = $this->createMock(ContainerInterface::class);

        $dispatcher->register('id', static function () use (&$calls): void {
            ++$calls;
        });

        $dispatcher->dispatch('id', new stdClass(), $container);

        self::assertSame(1, $calls);
    }
}
