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
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationSourceResolver::class)]
final class ConfigurationSourceResolverMutationTest extends TestCase
{
    private string $fixturesDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
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
}
