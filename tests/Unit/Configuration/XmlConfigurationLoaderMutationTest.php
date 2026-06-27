<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlConfigurationLoader::class)]
final class XmlConfigurationLoaderMutationTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
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
