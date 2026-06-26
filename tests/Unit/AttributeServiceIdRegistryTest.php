<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\AttributeServiceIdRegistry;
use CloudCastle\DI\Contract\ServiceIdAttribute;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributeReaderFixture;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\PlainServiceIdAttributeStub;
use CloudCastle\DI\Tests\Fixtures\Autowire\UnrelatedAttribute;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(AttributeServiceIdRegistry::class)]
#[CoversClass(AttributeServiceIdReader::class)]
final class AttributeServiceIdRegistryTest extends TestCase
{
    public function testRegisterAddsCustomAttribute(): void
    {
        $registry = new AttributeServiceIdRegistry();
        $registry->register(CustomServiceIdAttribute::class);

        self::assertTrue($registry->isKnown(CustomServiceIdAttribute::class));
    }

    public function testIsKnownRecognizesBuiltinAttributesWithoutRegister(): void
    {
        $registry = new AttributeServiceIdRegistry();

        self::assertTrue($registry->isKnown(Inject::class));
        self::assertFalse($registry->isKnown(CustomServiceIdAttribute::class));
    }

    public function testRegisterIsIdempotent(): void
    {
        $registry = new AttributeServiceIdRegistry();
        $registry->register(CustomServiceIdAttribute::class);
        $registry->register(CustomServiceIdAttribute::class);

        self::assertTrue($registry->isKnown(CustomServiceIdAttribute::class));
    }

    public function testRegisterRejectsClassWithoutServiceIdContract(): void
    {
        $registry = new AttributeServiceIdRegistry();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ServiceIdAttribute::class);

        $registry->register(UnrelatedAttribute::class);
    }

    public function testRegisterRejectsClassWithoutPhpAttribute(): void
    {
        $registry = new AttributeServiceIdRegistry();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('#[\\Attribute]');

        $registry->register(PlainServiceIdAttributeStub::class);
    }

    public function testReaderReadsRegisteredCustomAttribute(): void
    {
        $registry = new AttributeServiceIdRegistry();
        $registry->register(CustomServiceIdAttribute::class);

        $reader = new AttributeServiceIdReader($registry);
        $property = new ReflectionProperty(CustomAttributeReaderFixture::class, 'dependency');

        self::assertTrue($reader->hasAny($property->getAttributes()));
        self::assertSame('mailer', $reader->read($property->getAttributes()));
    }
}
