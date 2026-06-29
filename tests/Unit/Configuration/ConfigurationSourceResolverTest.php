<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationDirectoryScan;
use CloudCastle\DI\Configuration\ConfigurationDirectorySource;
use CloudCastle\DI\Configuration\ConfigurationFilesSource;
use CloudCastle\DI\Configuration\ConfigurationLoaderRegistry;
use CloudCastle\DI\Configuration\ConfigurationSource;
use CloudCastle\DI\Configuration\ConfigurationSourceResolver;
use CloudCastle\DI\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationSourceResolver::class)]
#[CoversClass(ConfigurationDirectorySource::class)]
#[CoversClass(ConfigurationFilesSource::class)]
final class ConfigurationSourceResolverTest extends TestCase
{
    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testResolveExpandsDirectoryPathString(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            $this->fixturesDirectory . '/layers',
        ]);

        self::assertCount(2, $layers);
        /** @var array<string, mixed> $firstServices */
        $firstServices = $layers[0]->config['services'];
        /** @var array<string, mixed> $secondServices */
        $secondServices = $layers[1]->config['services'];
        self::assertSame('from-layer-php', $firstServices['app.label']);
        self::assertSame('from-layer-json', $secondServices['app.label']);
    }

    public function testResolveExpandsConfigurationDirectorySourceRecursively(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationDirectorySource(
                $this->fixturesDirectory . '/nested',
                scan: ConfigurationDirectoryScan::Recursive,
            ),
        ]);

        self::assertCount(2, $layers);
        /** @var array<string, mixed> $rootServices */
        $rootServices = $layers[0]->config['services'];
        /** @var array<string, mixed> $childServices */
        $childServices = $layers[1]->config['services'];
        self::assertSame('nested-root', $rootServices['app.mode']);
        self::assertSame('from-nested-json', $childServices['app.label']);
    }

    public function testResolveExpandsConfigurationFilesSource(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationFilesSource([
                $this->fixturesDirectory . '/layers/01-base.php',
                $this->fixturesDirectory . '/layers/02-overlay.json',
            ]),
        ]);

        self::assertCount(2, $layers);
        /** @var array<string, mixed> $overlayServices */
        $overlayServices = $layers[1]->config['services'];
        self::assertSame('from-layer-json', $overlayServices['app.label']);
    }

    public function testResolveAppliesDirectoryPriorityToEachFile(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationDirectorySource($this->fixturesDirectory . '/layers', priority: 50),
        ]);

        self::assertSame(50, $layers[0]->filePriority);
        self::assertSame(50, $layers[1]->filePriority);
    }

    public function testResolveThrowsWhenDirectoryMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationDirectorySource($this->fixturesDirectory . '/missing-dir'),
        ]);
    }

    public function testResolveThrowsWhenFilesSourceEmpty(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не может быть пустым');

        (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationFilesSource([]),
        ]);
    }

    public function testResolveThrowsWhenFileMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationSource($this->fixturesDirectory . '/missing.php'),
        ]);
    }

    public function testLoaderRegistrySupportsKnownExtensions(): void
    {
        $registry = new ConfigurationLoaderRegistry();

        self::assertTrue($registry->supports($this->fixturesDirectory . '/base.php'));
        self::assertTrue($registry->supports($this->fixturesDirectory . '/override.json'));
        self::assertFalse($registry->supports($this->fixturesDirectory . '/unknown.ini'));
    }

    public function testResolveThrowsWhenSingleFilePathMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            $this->fixturesDirectory . '/missing.php',
        ]);
    }

    public function testResolveThrowsWhenConfigurationSourcePathMissing(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationSource($this->fixturesDirectory . '/missing.php'),
        ]);
    }

    public function testResolveThrowsWhenFileFormatUnsupported(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-unsupported.ini';
        file_put_contents($path, "key=value\n");

        try {
            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('не поддерживается');

            (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
                new ConfigurationSource($path),
            ]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testResolveThrowsWhenFilesSourceContainsUnsupportedFormat(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-files-unsupported.txt';
        file_put_contents($path, 'not-config');

        try {
            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('не поддерживается');

            (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
                new ConfigurationFilesSource([$path]),
            ]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testResolveThrowsWhenFileNotReadable(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-unreadable.php';
        file_put_contents($path, '<?php return [];');
        chmod($path, 0o000);

        try {
            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('не найден');

            (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
                $path,
            ]);
        } finally {
            chmod($path, 0o644);

            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testResolveExpandsSingleFilePathString(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            $this->fixturesDirectory . '/base.php',
        ]);

        self::assertCount(1, $layers);
        self::assertArrayHasKey('services', $layers[0]->config);
    }

    public function testResolveAppliesConfigurationSourcePriority(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationSource($this->fixturesDirectory . '/base.php', priority: 77),
        ]);

        self::assertSame(77, $layers[0]->filePriority);
    }

    public function testFlatDirectoryScanSkipsNestedFiles(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationDirectorySource(
                $this->fixturesDirectory . '/nested',
                scan: ConfigurationDirectoryScan::Flat,
            ),
        ]);

        self::assertCount(1, $layers);
        /** @var array<string, mixed> $services */
        $services = $layers[0]->config['services'];
        self::assertSame('nested-root', $services['app.mode']);
    }

    public function testDirectoryWithOnlyUnsupportedFilesProducesNoLayers(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-unsupported-only';

        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }

        file_put_contents($directory . '/readme.txt', 'skip');
        file_put_contents($directory . '/notes.md', 'skip');

        try {
            $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
                new ConfigurationDirectorySource($directory),
            ]);

            self::assertSame([], $layers);
        } finally {
            if (is_file($directory . '/readme.txt')) {
                unlink($directory . '/readme.txt');
            }

            if (is_file($directory . '/notes.md')) {
                unlink($directory . '/notes.md');
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testResolveAssignsIncrementingLayerOrder(): void
    {
        $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationFilesSource([
                $this->fixturesDirectory . '/layers/01-base.php',
                $this->fixturesDirectory . '/layers/02-overlay.json',
            ]),
        ]);

        self::assertSame(0, $layers[0]->order);
        self::assertSame(1, $layers[1]->order);
    }

    public function testResolveFilesSourceValidatesEachPath(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
            new ConfigurationFilesSource([
                $this->fixturesDirectory . '/base.php',
                $this->fixturesDirectory . '/also-missing.json',
            ]),
        ]);
    }

    public function testRecursiveScanFollowsSymlinkToConfigFile(): void
    {
        $directory = sys_get_temp_dir() . '/cloudcastle-di-symlink-' . uniqid('', true);
        $linkedDirectory = $directory . '/linked';
        mkdir($linkedDirectory, 0o755, true);
        symlink($this->fixturesDirectory . '/base.php', $linkedDirectory . '/base.php');

        try {
            $layers = (new ConfigurationSourceResolver(new ConfigurationLoaderRegistry()))->resolve([
                new ConfigurationDirectorySource(
                    $directory,
                    scan: ConfigurationDirectoryScan::Recursive,
                ),
            ]);

            self::assertCount(1, $layers);
            self::assertArrayHasKey('services', $layers[0]->config);
        } finally {
            if (is_link($linkedDirectory . '/base.php')) {
                unlink($linkedDirectory . '/base.php');
            }

            if (is_dir($linkedDirectory)) {
                rmdir($linkedDirectory);
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
