<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Attribute\Autowire;
use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\AttributeServiceIdRegistry;
use CloudCastle\DI\Tests\Fixtures\Autowire\AttributeReaderFixtures;
use CloudCastle\DI\Tests\Fixtures\Autowire\KnownNonServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyInjectAttributeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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

    public function testReadSkipsThrowingUnrelatedAttributeBeforeAutowire(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'beforeAutowire');

        self::assertSame('mailer', $reader->read($property->getAttributes()));
    }

    public function testReadDoesNotInstantiateThrowingUnrelatedAttribute(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'throwingOnly');

        self::assertNull($reader->read($property->getAttributes()));
        self::assertFalse($reader->hasAny($property->getAttributes()));
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

    public function testReadReturnsNullWhenKnownAttributeDoesNotImplementContract(): void
    {
        $registry = new AttributeServiceIdRegistry();
        $customClasses = (new ReflectionClass(AttributeServiceIdRegistry::class))->getProperty('customClasses');
        $customClasses->setValue($registry, [KnownNonServiceIdAttribute::class]);

        $reader = new AttributeServiceIdReader($registry);
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'knownWithoutContract');

        self::assertTrue($reader->hasAny($property->getAttributes()));
        self::assertNull($reader->read($property->getAttributes()));
    }
}
