<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ClassScanner;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MixedScanConcreteNext;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanAlpha;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\MultiScanBeta;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\ScannedService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\SpacedNamespaceService;
use CloudCastle\DI\Tests\Fixtures\Autowire\ScanOverflowService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Сканирование PHP-классов в каталоге.
 */
#[CoversClass(ClassScanner::class)]
final class ClassScannerTest extends TestCase
{
    private string $fixturesDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__) . '/Fixtures/Autowire';
    }

    public function testScanExtractsClassesFromDirectory(): void
    {
        $scanner = new ClassScanner();
        $classNames = $scanner->scan($this->fixturesDirectory);

        self::assertContains(SimpleService::class, $classNames);
        self::assertContains(Clock::class, $classNames);
        self::assertNotContains(AbstractWorker::class, $classNames);
    }

    public function testScanThrowsForMissingDirectory(): void
    {
        $scanner = new ClassScanner();

        $this->expectException(ContainerException::class);

        $scanner->scan('/tmp/cloudcastle-di-missing-directory');
    }

    public function testScanSkipsPhpFilesWithoutClass(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-scan-' . uniqid('', true);
        mkdir($directory);
        file_put_contents($directory . '/plain.php', '<?php declare(strict_types=1);');
        file_put_contents($directory . '/empty.php', '');
        file_put_contents($directory . '/readme.txt', 'not php');
        file_put_contents(
            $directory . '/Valid.php',
            '<?php declare(strict_types=1);'
            . ' namespace CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan;'
            . ' final class ValidScanService {}',
        );

        try {
            $scanner = new ClassScanner();

            self::assertSame([], $scanner->scan($directory));
        } finally {
            unlink($directory . '/Valid.php');
            unlink($directory . '/readme.txt');
            unlink($directory . '/empty.php');
            unlink($directory . '/plain.php');
            rmdir($directory);
        }
    }

    public function testScanFindsAutoloadedClassWhenEmptyPhpFileIsPresent(): void
    {
        $directory = $this->fixturesDirectory . '/Scan';
        $emptyFile = $directory . '/empty-scan.php';
        file_put_contents($emptyFile, '');

        try {
            $scanner = new ClassScanner();
            $classNames = $scanner->scan(
                $directory,
                'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
            );

            self::assertContains(ScannedService::class, $classNames);
            self::assertCount(5, $classNames);
        } finally {
            unlink($emptyFile);
        }
    }

    public function testScanSkipsNotAutoloadedClassName(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-scan-' . uniqid('', true);
        mkdir($directory);
        file_put_contents(
            $directory . '/Ghost.php',
            '<?php declare(strict_types=1); namespace CloudCastle\\DI\\Tests\\Fixtures\\Ghost; final class Ghost {}',
        );

        try {
            $scanner = new ClassScanner();

            self::assertSame([], $scanner->scan($directory));
        } finally {
            unlink($directory . '/Ghost.php');
            rmdir($directory);
        }
    }

    public function testScanFiltersClassesByNamespacePrefix(): void
    {
        $scanner = new ClassScanner();
        $classNames = $scanner->scan(
            $this->fixturesDirectory,
            'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
        );

        self::assertEqualsCanonicalizing(
            [
                MixedScanConcreteNext::class,
                ScannedService::class,
                SpacedNamespaceService::class,
                MultiScanAlpha::class,
                MultiScanBeta::class,
            ],
            $classNames,
        );

        $filtered = $scanner->scan(
            $this->fixturesDirectory,
            'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan',
        );

        self::assertContains(ScannedService::class, $filtered);
        self::assertNotContains(ScanOverflowService::class, $filtered);
    }

    public function testScanSkipsNonPhpFilesBeforePhpFilesInDirectory(): void
    {
        $directory = $this->fixturesDirectory . '/Scan';
        $marker = $directory . '/aaa-readme.txt';
        file_put_contents($marker, 'skip');

        try {
            $scanner = new ClassScanner();
            $classNames = $scanner->scan($directory);

            self::assertContains(ScannedService::class, $classNames);
            self::assertContains(SpacedNamespaceService::class, $classNames);
        } finally {
            unlink($marker);
        }
    }

    public function testScanContinuesAfterNonPhpFileListedBeforePhpFile(): void
    {
        $directory = $this->fixturesDirectory . '/Scan';
        $marker = $directory . '/000-skip.txt';
        file_put_contents($marker, 'not php');

        try {
            $scanner = new ClassScanner();
            $classNames = $scanner->scan(
                $directory,
                'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
            );

            self::assertContains(ScannedService::class, $classNames);
            self::assertContains(SpacedNamespaceService::class, $classNames);
        } finally {
            unlink($marker);
        }
    }

    public function testScanSkipsUnreadablePhpFile(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-scan-' . uniqid('', true);
        mkdir($directory);
        $path = $directory . '/unreadable.php';
        file_put_contents($path, '<?php declare(strict_types=1); final class Unreadable {}');

        try {
            chmod($path, 0o000);
            $scanner = new ClassScanner();

            self::assertSame([], $scanner->scan($directory));
        } finally {
            chmod($path, 0o644);
            unlink($path);
            rmdir($directory);
        }
    }

    public function testScanSkipsAbstractClassFile(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-scan-' . uniqid('', true);
        mkdir($directory);
        file_put_contents(
            $directory . '/AbstractOnly.php',
            '<?php declare(strict_types=1);'
            . ' namespace CloudCastle\\DI\\Tests\\Fixtures\\Autowire;'
            . ' abstract class AbstractOnly {}',
        );

        try {
            $scanner = new ClassScanner();

            self::assertSame([], $scanner->scan($directory));
        } finally {
            unlink($directory . '/AbstractOnly.php');
            rmdir($directory);
        }
    }
}
