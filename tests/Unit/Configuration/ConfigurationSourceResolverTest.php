<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

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
#[CoversClass(ConfigurationDirectorySource::class)]
#[CoversClass(ConfigurationFilesSource::class)]
final class ConfigurationSourceResolverTest extends TestCase
{
    private string $fixturesDirectory;

    #[Override]
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
            new ConfigurationDirectorySource($this->fixturesDirectory . '/nested', recursive: true),
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
}
