<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\Loader\YamlConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(YamlConfigurationLoader::class)]
#[RequiresPhpExtension('yaml')]
final class ConfigurationLoaderYamlTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    private string $fixturesDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testSupportsYamlExtensions(): void
    {
        $loader = new YamlConfigurationLoader();

        self::assertTrue($loader->supports($this->fixturesDirectory . '/overlay.yaml'));
        self::assertTrue($loader->supports($this->fixturesDirectory . '/overlay.yml'));
        self::assertFalse($loader->supports($this->fixturesDirectory . '/override.json'));
    }

    public function testLoadParsesValidFile(): void
    {
        $config = (new YamlConfigurationLoader())->load($this->fixturesDirectory . '/overlay.yaml');
        $services = $this->assertConfigMap($config, 'services');

        self::assertSame('from-yaml', $services['app.label']);
    }

    public function testLoadThrowsForMissingFile(): void
    {
        $loader = new YamlConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $loader->load($this->fixturesDirectory . '/missing.yaml');
    }

    public function testLoadThrowsForInvalidYaml(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-invalid.yaml';
        file_put_contents($path, "services: [\n  unclosed");

        try {
            $loader = new YamlConfigurationLoader();

            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('Ошибка разбора YAML');

            $loader->load($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testLoadThrowsWhenRootIsNotMapping(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-scalar.yaml';
        file_put_contents($path, 'just-a-string');

        try {
            $loader = new YamlConfigurationLoader();

            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('должна быть mapping');

            $loader->load($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testLoadThrowsWhenParserReturnsFalse(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-empty.yaml';
        file_put_contents($path, '');

        try {
            $loader = new YamlConfigurationLoader();

            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('Ошибка разбора YAML');

            $loader->load($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
