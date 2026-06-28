<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ClassScanner;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanAlpha;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanBeta;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassScanner::class)]
final class ClassScannerMutationTest extends TestCase
{
    private string $scanDirectory;

    protected function setUp(): void
    {
        $this->scanDirectory = \dirname(__DIR__) . '/Fixtures/Autowire/Scan';
    }

    public function testScanSkipsNonPhpFilesAndContinuesScanningPhpSources(): void
    {
        $noisePath = $this->scanDirectory . '/AAAA-mutation-noise.txt';
        file_put_contents($noisePath, 'noise');

        try {
            $classNames = (new ClassScanner())->scan(
                $this->scanDirectory,
                'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
            );

            self::assertContains(MultiScanAlpha::class, $classNames);
            self::assertContains(MultiScanBeta::class, $classNames);
        } finally {
            if (is_file($noisePath)) {
                unlink($noisePath);
            }
        }
    }
}
