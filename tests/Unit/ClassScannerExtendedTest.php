<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ClassScanner;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanAlpha;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanBeta;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\ScannedService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\ScannedStatus;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassScanner::class)]
final class ClassScannerExtendedTest extends TestCase
{
    private string $scanDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->scanDirectory = \dirname(__DIR__) . '/Fixtures/Autowire/Scan';
    }

    public function testScanFindsMultipleClassesInSingleFile(): void
    {
        $scanner = new ClassScanner();
        $classNames = $scanner->scan(
            $this->scanDirectory,
            'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
        );

        self::assertContains(MultiScanAlpha::class, $classNames);
        self::assertContains(MultiScanBeta::class, $classNames);
        self::assertContains(ScannedService::class, $classNames);
    }

    public function testScanSkipsNonInstantiableEnum(): void
    {
        $scanner = new ClassScanner();
        $classNames = $scanner->scan(
            $this->scanDirectory,
            'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
        );

        self::assertNotContains(ScannedStatus::class, $classNames);
    }
}
