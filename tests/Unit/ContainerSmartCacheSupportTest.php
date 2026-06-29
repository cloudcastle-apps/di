<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ContainerSmartCacheSupport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ContainerSmartCacheSupport::class)]
final class ContainerSmartCacheSupportTest extends TestCase
{
    private float $now = 1_000.0;

    private ContainerSmartCacheSupport $support;

    /** @var array<string, mixed> */
    private array $resolved = [];

    protected function setUp(): void
    {
        $this->now = 1_000.0;
        $this->support = new ContainerSmartCacheSupport(clock: fn (): float => $this->now);
        $this->resolved = [];
    }

    public function testEvictIfExpiredRemovesEntryWithoutTimestamp(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 10);
        $this->resolved['svc'] = new stdClass();

        $this->support->evictIfExpired('svc', [], $this->resolved);

        self::assertArrayNotHasKey('svc', $this->resolved);
    }

    public function testEvictIfExpiredRemovesStaleSingleton(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 10);
        $this->resolved['svc'] = new stdClass();
        $this->support->touch('svc');
        $this->now += 10.0;

        $this->support->evictIfExpired('svc', [], $this->resolved);

        self::assertArrayNotHasKey('svc', $this->resolved);
    }

    public function testEvictIfExpiredKeepsFreshSingleton(): void
    {
        $instance = new stdClass();
        $this->support->configureFor('svc', ttlSeconds: 30);
        $this->resolved['svc'] = $instance;
        $this->support->touch('svc');
        $this->now += 5.0;

        $this->support->evictIfExpired('svc', [], $this->resolved);

        self::assertSame($instance, $this->resolved['svc']);
    }

    public function testEvictIfExpiredUsesTagTtl(): void
    {
        $this->support->configureTagFor('group', ttlSeconds: 5);
        $this->resolved['svc'] = new stdClass();
        $this->support->touch('svc');
        $this->now += 5.0;

        $this->support->evictIfExpired('svc', ['group'], $this->resolved);

        self::assertArrayNotHasKey('svc', $this->resolved);
    }

    public function testForgetAllClearsResolvedAndTimestamps(): void
    {
        $this->resolved['first'] = new stdClass();
        $this->resolved['second'] = new stdClass();
        $this->support->touch('first');
        $this->support->forgetAll($this->resolved);

        self::assertSame([], $this->resolved);
        self::assertFalse($this->support->stats('first', [], $this->resolved)['cached']);
    }

    public function testStatsReportsExpirationState(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 20);
        $this->resolved['svc'] = new stdClass();
        $this->support->touch('svc');

        $stats = $this->support->stats('svc', [], $this->resolved);

        self::assertTrue($stats['configured']);
        self::assertSame(20, $stats['ttl_seconds']);
        self::assertTrue($stats['cached']);
        self::assertSame(1_020.0, $stats['expires_at']);
        self::assertFalse($stats['expired']);
    }

    public function testStatsMarksExpiredOnExactTtlBoundary(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 10);
        $this->resolved['svc'] = new stdClass();
        $this->support->touch('svc');
        $this->now = 1_010.0;

        self::assertTrue($this->support->stats('svc', [], $this->resolved)['expired']);
    }

    public function testStatsMarksExpiredWhenCachedWithoutTimestamp(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 60);
        $this->resolved['svc'] = new stdClass();

        self::assertTrue($this->support->stats('svc', [], $this->resolved)['expired']);
    }

    public function testEvictIfExpiredUsesShortestTagTtlAmongMultipleTags(): void
    {
        $this->support->configureTagFor('slow', ttlSeconds: 120);
        $this->support->configureTagFor('fast', ttlSeconds: 5);
        $this->resolved['svc'] = new stdClass();
        $this->support->touch('svc');
        $this->now += 5.0;

        $this->support->evictIfExpired('svc', ['slow', 'fast'], $this->resolved);

        self::assertArrayNotHasKey('svc', $this->resolved);
    }

    public function testForgetRemovesCachedEntryAndTimestamp(): void
    {
        $this->resolved['svc'] = new stdClass();
        $this->support->touch('svc');
        $this->support->forget('svc', $this->resolved);

        self::assertFalse($this->support->stats('svc', [], $this->resolved)['cached']);
    }

    public function testStatsReturnsNullExpiresAtWhenCachedWithoutTimestamp(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 10);
        $this->resolved['svc'] = new stdClass();

        self::assertNull($this->support->stats('svc', [], $this->resolved)['expires_at']);
    }

    public function testStatsExpiresAtIsFloatWhenTtlConfigured(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 10);
        $this->resolved['svc'] = new stdClass();
        $this->support->touch('svc');

        $expiresAt = $this->support->stats('svc', [], $this->resolved)['expires_at'];

        self::assertIsFloat($expiresAt);
        self::assertSame(1_010.0, $expiresAt);
    }

    public function testStatsReportsNotExpiredWhenServiceIsNotCached(): void
    {
        $this->support->configureFor('svc', ttlSeconds: 30);

        self::assertFalse($this->support->stats('svc', [], $this->resolved)['expired']);
    }
}
