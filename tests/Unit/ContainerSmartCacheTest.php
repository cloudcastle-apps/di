<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerSmartCacheSupport;
use CloudCastle\DI\Tests\Support\ContainerInternalAccess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
#[CoversClass(ContainerSmartCacheSupport::class)]
final class ContainerSmartCacheTest extends TestCase
{
    public function testForgetForcesNewSingletonOnNextGet(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('svc', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });

        $container->get('svc');
        $container->get('svc');
        ContainerInternalAccess::forget($container, 'svc');
        $container->get('svc');

        self::assertSame(2, $calls);
    }

    public function testCacheForExpiresSingletonAfterTtl(): void
    {
        $clock = new class () {
            public float $now = 1_000.0;
        };
        $container = new Container(smartCacheClock: fn (): float => $clock->now);

        $calls = 0;
        $container->set('svc', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        ContainerInternalAccess::cacheFor($container, 'svc', ttlSeconds: 10);
        $container->get('svc');
        $clock->now += 10.0;
        $container->get('svc');

        self::assertSame(2, $calls);
    }

    public function testSetInvalidatesSingletonCache(): void
    {
        $container = new Container();
        $container->set('svc', static fn (): stdClass => new stdClass());
        self::assertInstanceOf(stdClass::class, $cached = $container->get('svc'));
        $container->set('svc', static fn (): stdClass => new stdClass());
        self::assertInstanceOf(stdClass::class, $fresh = $container->get('svc'));

        self::assertNotSame($cached, $fresh);
    }

    public function testBindInvalidatesTargetSingletonCache(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('impl', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->get('impl');
        $container->bind('contract', 'impl');
        $container->get('contract');

        self::assertSame(2, $calls);
    }

    public function testCacheTagForAppliesToTaggedServices(): void
    {
        $container = new Container();
        $container->set('alpha', static fn (): stdClass => new stdClass());
        $container->set('beta', static fn (): stdClass => new stdClass());
        $container->tag('alpha', 'workers');
        $container->tag('beta', 'workers');
        ContainerInternalAccess::cacheTagFor($container, 'workers', ttlSeconds: 60);

        self::assertTrue(ContainerInternalAccess::cacheStats($container, 'alpha')['configured']);
        self::assertTrue(ContainerInternalAccess::cacheStats($container, 'beta')['configured']);
    }

    public function testForgetTagClearsTaggedSingletons(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('alpha', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->set('beta', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->tag('alpha', 'batch');
        $container->tag('beta', 'batch');

        $container->get('alpha');
        $container->get('beta');
        ContainerInternalAccess::forgetTag($container, 'batch');
        $container->get('alpha');
        $container->get('beta');

        self::assertSame(4, $calls);
    }

    public function testForgetAllClearsEverySingleton(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('first', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->set('second', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });

        $container->get('first');
        $container->get('second');
        ContainerInternalAccess::forgetAll($container);
        $container->get('first');
        $container->get('second');

        self::assertSame(4, $calls);
    }

    public function testMakeIgnoresSmartCache(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('proto', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        ContainerInternalAccess::cacheFor($container, 'proto', ttlSeconds: 3600);

        $container->make('proto');
        $container->make('proto');

        self::assertSame(2, $calls);
    }

    public function testCacheStatsReportsExpiredAfterTtlElapses(): void
    {
        $clock = new class () {
            public float $now = 1_000.0;
        };
        $container = new Container(smartCacheClock: fn (): float => $clock->now);
        $container->set('svc', static fn (): stdClass => new stdClass());
        ContainerInternalAccess::cacheFor($container, 'svc', ttlSeconds: 15);
        $container->get('svc');

        $clock->now = 1_015.0;

        self::assertTrue(ContainerInternalAccess::cacheStats($container, 'svc')['expired']);
    }

    public function testCacheStatsReportsExpiresAtAfterGet(): void
    {
        $clock = new class () {
            public float $now = 1_000.0;
        };
        $container = new Container(smartCacheClock: fn (): float => $clock->now);
        $container->set('svc', static fn (): stdClass => new stdClass());
        ContainerInternalAccess::cacheFor($container, 'svc', ttlSeconds: 20);
        $container->get('svc');

        self::assertSame(1_020.0, ContainerInternalAccess::cacheStats($container, 'svc')['expires_at']);
    }

    public function testTouchTimestampNotUpdatedOnSubsequentGetWithinTtl(): void
    {
        $clock = new class () {
            public float $now = 1_000.0;
        };
        $container = new Container(smartCacheClock: fn (): float => $clock->now);
        $container->set('svc', static fn (): stdClass => new stdClass());
        ContainerInternalAccess::cacheFor($container, 'svc', ttlSeconds: 30);
        $container->get('svc');

        $clock->now = 1_005.0;
        $container->get('svc');

        self::assertSame(1_030.0, ContainerInternalAccess::cacheStats($container, 'svc')['expires_at']);
    }

    public function testMakeDoesNotUpdateTouchTimestamp(): void
    {
        $clock = new class () {
            public float $now = 1_000.0;
        };
        $container = new Container(smartCacheClock: fn (): float => $clock->now);
        $container->set('svc', static fn (): stdClass => new stdClass());
        ContainerInternalAccess::cacheFor($container, 'svc', ttlSeconds: 25);
        $container->get('svc');

        $clock->now = 1_010.0;
        $container->make('svc');

        self::assertSame(1_025.0, ContainerInternalAccess::cacheStats($container, 'svc')['expires_at']);
    }

    public function testCacheStatsReportsNotExpiredWhenServiceIsNotCached(): void
    {
        $container = new Container();
        $container->set('svc', static fn (): stdClass => new stdClass());
        ContainerInternalAccess::cacheFor($container, 'svc', ttlSeconds: 60);

        self::assertFalse(ContainerInternalAccess::cacheStats($container, 'svc')['expired']);
    }

    public function testGetEvictsUsingShortestTtlAmongMultipleServiceTags(): void
    {
        $clock = new class () {
            public float $now = 1_000.0;
        };
        $container = new Container(smartCacheClock: fn (): float => $clock->now);
        $calls = 0;
        $container->set('svc', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->tag('svc', 'slow');
        $container->tag('svc', 'fast');
        ContainerInternalAccess::cacheTagFor($container, 'slow', ttlSeconds: 120);
        ContainerInternalAccess::cacheTagFor($container, 'fast', ttlSeconds: 5);
        $container->get('svc');

        $clock->now += 5.0;
        $container->get('svc');

        self::assertSame(2, $calls);
    }
}
