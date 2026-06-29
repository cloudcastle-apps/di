<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ContainerConfigurator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(ContainerConfigurator::class)]
final class ContainerConfiguratorVisibilityTest extends TestCase
{
    public function testApplyMethodIsPublic(): void
    {
        $method = new ReflectionMethod(ContainerConfigurator::class, 'apply');

        self::assertTrue($method->isPublic());
    }
}
