<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\ContainerCompileResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerCompileResult::class)]
final class ContainerCompileResultTest extends TestCase
{
    public function testStoresClassNameAndOutputPath(): void
    {
        $result = new ContainerCompileResult(
            'App\\Compiled\\DiContainer',
            '/tmp/CompiledDiContainer.php',
        );

        self::assertSame('App\\Compiled\\DiContainer', $result->className);
        self::assertSame('/tmp/CompiledDiContainer.php', $result->outputPath);
    }
}
