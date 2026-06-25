<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Attribute\Autowire;
use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\Tests\Fixtures\Autowire\AttributeReaderFixtures;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyInjectAttributeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Чтение PHP attributes {@see Inject} / {@see Autowire}.
 */
#[CoversClass(Inject::class)]
#[CoversClass(Autowire::class)]
#[CoversClass(AttributeServiceIdReader::class)]
final class AttributeServiceIdReaderTest extends TestCase
{
    public function testReadReturnsAutowireServiceId(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'withAutowireService');

        self::assertSame('mailer', $reader->read($property->getAttributes()));
    }

    public function testReadReturnsNullForUnrelatedAttribute(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'unrelated');

        self::assertNull($reader->read($property->getAttributes()));
    }

    public function testReadReturnsInjectIdFromPropertyAttribute(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(PropertyInjectAttributeService::class, 'clock');

        self::assertSame('app.clock', $reader->read($property->getAttributes()));
    }

    public function testHasAnyDetectsInjectAttributeWithoutId(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'withInjectWithoutId');
        $attributes = $property->getAttributes();

        self::assertTrue($reader->hasAny($attributes));
        self::assertNull($reader->read($attributes));
    }
}
