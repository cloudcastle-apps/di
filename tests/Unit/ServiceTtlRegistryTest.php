<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\ServiceTtlRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServiceTtlRegistry::class)]
final class ServiceTtlRegistryTest extends TestCase
{
    private ServiceTtlRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ServiceTtlRegistry();
    }

    public function testEffectiveTtlReturnsNullWhenNotConfigured(): void
    {
        self::assertNull($this->registry->effectiveTtl('svc', []));
    }

    public function testServiceTtlOverridesTagTtl(): void
    {
        $this->registry->setServiceTtl('svc', 30);
        $this->registry->setTagTtl('group', 120);

        self::assertSame(30, $this->registry->effectiveTtl('svc', ['group']));
    }

    public function testEffectiveTtlUsesMinimumAmongTags(): void
    {
        $this->registry->setTagTtl('slow', 120);
        $this->registry->setTagTtl('fast', 15);

        self::assertSame(15, $this->registry->effectiveTtl('svc', ['slow', 'fast']));
    }

    public function testSetServiceTtlRejectsZero(): void
    {
        $this->expectException(ContainerException::class);

        $this->registry->setServiceTtl('svc', 0);
    }

    public function testSetTagTtlRejectsZero(): void
    {
        $this->expectException(ContainerException::class);

        $this->registry->setTagTtl('group', 0);
    }
}
