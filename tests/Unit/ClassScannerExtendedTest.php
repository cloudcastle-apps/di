<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ClassScanner;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MixedScanConcreteNext;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanAlpha;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanBeta;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\ScannedService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\ScannedStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassScanner::class)]
final class ClassScannerExtendedTest extends TestCase
{
    private string $scanDirectory;

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

    public function testScanSkipsAbstractClass(): void
    {
        $scanner = new ClassScanner();
        $classNames = $scanner->scan(
            \dirname(__DIR__) . '/Fixtures/Autowire',
            'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\',
        );

        self::assertNotContains(\CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker::class, $classNames);
    }

    public function testScanFindsConcreteClassAfterAbstractInSameFile(): void
    {
        $scanner = new ClassScanner();
        $classNames = $scanner->scan(
            $this->scanDirectory,
            'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
        );

        self::assertContains(MixedScanConcreteNext::class, $classNames);
    }

    public function testScanIgnoresEmptyPhpFile(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-scan-' . uniqid();
        mkdir($directory);
        file_put_contents($directory . '/Empty.php', '');

        try {
            $scanner = new ClassScanner();
            $classNames = $scanner->scan($directory);

            self::assertSame([], $classNames);
        } finally {
            if (is_file($directory . '/Empty.php')) {
                unlink($directory . '/Empty.php');
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testExtractDeclaredTypeNamesReturnsEmptyWhenPatternMatchingFails(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-scan-' . uniqid('', true);
        mkdir($directory);
        $path = $directory . '/Backtrack.php';
        file_put_contents(
            $path,
            '<?php declare(strict_types=1); namespace CloudCastle\\DI\\Tests\\Fixtures;'
            . str_repeat(' class BacktrackTarget', 5000),
        );

        $previousLimit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '1');

        try {
            $method = new \ReflectionMethod(ClassScanner::class, 'extractDeclaredTypeNames');
            $method->setAccessible(true);

            self::assertSame([], $method->invoke(new ClassScanner(), $path));
        } finally {
            ini_set('pcre.backtrack_limit', (string) $previousLimit);

            if (is_file($path)) {
                unlink($path);
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
