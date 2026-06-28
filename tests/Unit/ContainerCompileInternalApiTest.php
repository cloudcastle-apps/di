<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
final class ContainerCompileInternalApiTest extends TestCase
{
    public function testExportDefinitionsReturnsRegisteredDefinitions(): void
    {
        $container = new Container();
        $clock = new Clock();
        $container->set('app.clock', $clock);
        $container->set('app.label', 'compiled');

        self::assertSame(
            [
                'app.clock' => $clock,
                'app.label' => 'compiled',
            ],
            $container->exportDefinitions(),
        );
    }

    public function testHasAfterResolvingCallbacksReturnsFalseByDefault(): void
    {
        self::assertFalse((new Container())->hasAfterResolvingCallbacks());
    }

    public function testHasAfterResolvingCallbacksReturnsTrueAfterRegistration(): void
    {
        $container = new Container();
        $container->afterResolving(Clock::class, static function (): void {
        });

        self::assertTrue($container->hasAfterResolvingCallbacks());
    }
}
