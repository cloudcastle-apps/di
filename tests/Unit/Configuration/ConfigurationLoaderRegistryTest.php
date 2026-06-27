<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationLoaderRegistry;
use CloudCastle\DI\Configuration\ConfigurationSource;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationLoaderRegistry::class)]
#[CoversClass(ConfigurationSource::class)]
final class ConfigurationLoaderRegistryTest extends TestCase
{
    private string $fixturesDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testConfigurationSourceExposesPathAndPriority(): void
    {
        $source = new ConfigurationSource($this->fixturesDirectory . '/base.php', 42);

        self::assertSame($this->fixturesDirectory . '/base.php', $source->path);
        self::assertSame(42, $source->priority);
    }

    public function testLoaderRegistryThrowsForUnsupportedFormat(): void
    {
        $registry = new ConfigurationLoaderRegistry();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не поддерживается');

        $registry->load($this->fixturesDirectory . '/unknown.ini');
    }

    public function testLoaderRegistryAcceptsCustomLoaderList(): void
    {
        $registry = new ConfigurationLoaderRegistry([new PhpConfigurationLoader()]);

        $config = $registry->load($this->fixturesDirectory . '/base.php');

        self::assertIsArray($config['services']);
    }

    public function testLoaderRegistryUsesDefaultLoadersWhenNull(): void
    {
        $registry = new ConfigurationLoaderRegistry();
        $config = $registry->load(\dirname(__DIR__, 2) . '/Fixtures/Config/base.php');

        self::assertIsArray($config['services'] ?? null);
    }
}
