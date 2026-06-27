<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\YamlConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonConfigurationLoader::class)]
#[CoversClass(PhpConfigurationLoader::class)]
#[CoversClass(YamlConfigurationLoader::class)]
#[CoversClass(XmlConfigurationLoader::class)]
final class ConfigurationLoaderCoverageTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testJsonLoaderReadsPriorityWrapper(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-priority.json';
        file_put_contents($path, '{"services":{"k":{"value":"v","priority":10}}}');

        try {
            $config = (new JsonConfigurationLoader())->load($path);
            $services = $this->assertConfigMap($config, 'services');

            self::assertSame(['value' => 'v', 'priority' => 10], $services['k']);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testPhpLoaderThrowsForMissingFile(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        (new PhpConfigurationLoader())->load(sys_get_temp_dir() . '/cloudcastle-di-missing.php');
    }

    public function testYamlLoaderCoversOverlayFixtureWhenExtensionAvailable(): void
    {
        if (!\function_exists('yaml_parse_file')) {
            self::markTestSkipped('Для YAML-тестов требуется ext-yaml.');
        }

        $this->assertConfigArray((new YamlConfigurationLoader())->load($this->fixturesDirectory . '/overlay.yaml'));
    }

    public function testXmlLoaderReadsAliasAndBindPriority(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-bind-alias.xml';
        file_put_contents(
            $path,
            '<?xml version="1.0"?><container>'
            . '<bind><binding abstract="a" concrete="b" priority="3"/></bind>'
            . '<aliases><alias name="x" target="y" priority="4"/></aliases>'
            . '</container>',
        );

        try {
            $config = (new XmlConfigurationLoader())->load($path);
            $bind = $this->assertConfigMap($config, 'bind');
            $aliases = $this->assertConfigMap($config, 'aliases');

            self::assertSame(['value' => 'b', 'priority' => 3], $bind['a']);
            self::assertSame(['value' => 'y', 'priority' => 4], $aliases['x']);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testXmlLoaderParsesTagsAndScanSections(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-tags-scan.xml';
        file_put_contents(
            $path,
            '<?xml version="1.0"?><container>'
            . '<scan><directory path="/src" namespace="App"/></scan>'
            . '<tags><tag name="t"><id>a</id><id>b</id></tag></tags>'
            . '</container>',
        );

        try {
            $config = (new XmlConfigurationLoader())->load($path);
            $scan = $this->assertConfigList($config, 'scan');
            $tags = $this->assertConfigMap($config, 'tags');

            self::assertSame([['directory' => '/src', 'namespace' => 'App']], $scan);
            self::assertSame(['a', 'b'], $tags['t']);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
