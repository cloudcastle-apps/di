<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\YamlConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpConfigurationLoader::class)]
#[CoversClass(JsonConfigurationLoader::class)]
#[CoversClass(XmlConfigurationLoader::class)]
#[CoversClass(YamlConfigurationLoader::class)]
final class ConfigurationLoaderTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testPhpLoaderSupportsPhpExtension(): void
    {
        $loader = new PhpConfigurationLoader();

        self::assertTrue($loader->supports($this->fixturesDirectory . '/base.php'));
        self::assertFalse($loader->supports($this->fixturesDirectory . '/override.json'));
    }

    public function testJsonLoaderParsesFile(): void
    {
        $loader = new JsonConfigurationLoader();
        $config = $loader->load($this->fixturesDirectory . '/override.json');

        self::assertIsArray($config['services'] ?? null);
        /** @var array<string, mixed> $services */
        $services = $config['services'];
        self::assertSame('from-json', $services['app.label']);
    }

    public function testJsonLoaderSupportsOnlyJsonExtension(): void
    {
        $loader = new JsonConfigurationLoader();

        self::assertTrue($loader->supports($this->fixturesDirectory . '/override.json'));
        self::assertFalse($loader->supports($this->fixturesDirectory . '/base.php'));
    }

    public function testXmlLoaderParsesFile(): void
    {
        $loader = new XmlConfigurationLoader();
        $config = $loader->load($this->fixturesDirectory . '/priority.xml');

        self::assertIsArray($config['services'] ?? null);
        /** @var array<string, mixed> $services */
        $services = $config['services'];
        self::assertIsArray($services['app.label']);
        /** @var array{value: string, priority: int} $label */
        $label = $services['app.label'];
        self::assertSame('from-xml', $label['value']);
        self::assertSame(100, $label['priority']);
        self::assertIsArray($config['autowiring'] ?? null);
        /** @var array<string, mixed> $autowiring */
        $autowiring = $config['autowiring'];
        self::assertTrue($autowiring['enabled'] ?? false);
    }

    public function testXmlLoaderParsesComprehensiveFile(): void
    {
        $loader = new XmlConfigurationLoader();
        $config = $loader->load($this->fixturesDirectory . '/comprehensive.xml');

        self::assertIsArray($config['services'] ?? null);
        self::assertIsArray($config['bind'] ?? null);
        self::assertIsArray($config['tags'] ?? null);
        self::assertIsArray($config['register_attributes'] ?? null);
        self::assertIsArray($config['autowiring'] ?? null);
    }

    public function testYamlLoaderParsesOverlayFixture(): void
    {
        $loader = new YamlConfigurationLoader();

        self::assertTrue($loader->supports($this->fixturesDirectory . '/overlay.yaml'));
        self::assertTrue($loader->supports($this->fixturesDirectory . '/overlay.yml'));

        $path = $this->fixturesDirectory . '/overlay.yaml';

        $config = $loader->load($path);
        /** @var array<string, mixed> $services */
        $services = $config['services'];
        self::assertSame('from-yaml', $services['app.label']);
    }

    public function testXmlLoaderReadsAutowireClassNameAttribute(): void
    {
        $loader = new XmlConfigurationLoader();
        $config = $loader->load($this->fixturesDirectory . '/autowire-attribute.xml');

        self::assertIsArray($config['autowire']);
        /** @var list<string> $autowire */
        $autowire = $config['autowire'];
        self::assertSame(Clock::class, $autowire[0]);
    }

    public function testLoadThrowsWhenFileIsMissing(): void
    {
        $loader = new XmlConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $loader->load($this->fixturesDirectory . '/missing.xml');
    }

    public function testAutowiringFalseFlagsAreOmitted(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-autowiring-false.xml';
        file_put_contents(
            $path,
            '<?xml version="1.0"?><container><autowiring enabled="false"/></container>',
        );

        try {
            $config = (new XmlConfigurationLoader())->load($path);

            self::assertSame([], $config['autowiring'] ?? []);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testParseAutowireSkipsEmptyClassElements(): void
    {
        self::assertCount(1, $this->assertConfigList(
            (new XmlConfigurationLoader())->load($this->fixturesDirectory . '/xml-details.xml'),
            'autowire',
        ));
    }

    public function testSupportsUsesLowercaseExtension(): void
    {
        $loader = new XmlConfigurationLoader();

        self::assertTrue($loader->supports('/path/config.Xml'));
    }
}
