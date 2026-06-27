<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlConfigurationLoader::class)]
#[CoversClass(JsonConfigurationLoader::class)]
final class ConfigurationLoaderDetailsTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    private string $fixturesDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testXmlLoaderParsesComprehensiveFileWithExactValues(): void
    {
        $config = (new XmlConfigurationLoader())->load($this->fixturesDirectory . '/comprehensive.xml');
        $bind = $this->assertConfigMap($config, 'bind');
        $tags = $this->assertConfigMap($config, 'tags');
        $autowiring = $this->assertConfigMap($config, 'autowiring');
        $services = $this->assertConfigMap($config, 'services');
        $aliases = $this->assertConfigMap($config, 'aliases');
        $autowire = $this->assertConfigList($config, 'autowire');
        $registerAttributes = $this->assertConfigList($config, 'register_attributes');

        self::assertSame(FileLogger::class, $bind['xml.bind']);
        self::assertSame([FileLogger::class], $autowire);
        self::assertSame(['xml.handler'], $tags['handlers']);
        self::assertSame([CustomServiceIdAttribute::class], $registerAttributes);
        self::assertTrue($autowiring['enabled']);
        self::assertTrue($autowiring['parameter_name']);
        self::assertTrue($autowiring['property']);
        self::assertTrue($autowiring['method']);
        self::assertSame(['class' => FileLogger::class, 'lazy' => true], $services['xml.handler']);
        self::assertSame('configured', $services['xml.scalar']);
        self::assertSame('xml.handler', $aliases['handler']);
    }

    public function testXmlLoaderParsesDetailsFixture(): void
    {
        $config = (new XmlConfigurationLoader())->load($this->fixturesDirectory . '/xml-details.xml');
        $bind = $this->assertConfigMap($config, 'bind');
        $aliases = $this->assertConfigMap($config, 'aliases');
        $services = $this->assertConfigMap($config, 'services');
        $scan = $this->assertConfigList($config, 'scan');
        $autowire = $this->assertConfigList($config, 'autowire');

        self::assertSame(25, $config['priority']);
        self::assertSame([['directory' => '/app/src', 'namespace' => 'App\Domain']], $scan);
        self::assertSame(['value' => FileLogger::class, 'priority' => 10], $bind['contract.logger']);
        self::assertSame(['value' => 'contract.logger', 'priority' => 5], $aliases['logger']);
        self::assertSame([Clock::class], $autowire);
        self::assertSame(['value' => 'from-xml-scalar', 'priority' => 50], $services['app.label']);
        self::assertSame(['class' => FileLogger::class, 'lazy' => false], $services['app.service']);
    }

    public function testXmlLoaderSupportsExtensionCaseInsensitively(): void
    {
        $loader = new XmlConfigurationLoader();

        self::assertTrue($loader->supports($this->fixturesDirectory . '/priority.XML'));
        self::assertFalse($loader->supports($this->fixturesDirectory . '/priority.json'));
    }

    public function testJsonLoaderSupportsExtension(): void
    {
        $loader = new JsonConfigurationLoader();

        self::assertTrue($loader->supports($this->fixturesDirectory . '/override.JSON'));
        self::assertFalse($loader->supports($this->fixturesDirectory . '/base.php'));
    }

    public function testJsonLoaderThrowsForMissingFile(): void
    {
        $loader = new JsonConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $loader->load($this->fixturesDirectory . '/missing.json');
    }
}
