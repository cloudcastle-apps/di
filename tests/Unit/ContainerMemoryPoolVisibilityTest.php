<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerMemoryPoolSupport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(Container::class)]
#[CoversClass(ContainerMemoryPoolSupport::class)]
final class ContainerMemoryPoolVisibilityTest extends TestCase
{
    public function testPoolingApiMethodsRemainPublic(): void
    {
        $methods = [
            'enablePooling',
            'disablePooling',
            'isPoolingEnabled',
            'releaseToPool',
            'clearPool',
            'clearAllPools',
            'poolStats',
        ];

        foreach ($methods as $methodName) {
            $method = new ReflectionMethod(Container::class, $methodName);

            self::assertTrue($method->isPublic(), $methodName);
        }
    }
}
