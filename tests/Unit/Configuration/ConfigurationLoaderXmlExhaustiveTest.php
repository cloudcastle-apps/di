<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlConfigurationLoader::class)]
final class ConfigurationLoaderXmlExhaustiveTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    private string $tempPath;

    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir() . '/cloudcastle-di-exhaustive.xml';
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempPath)) {
            unlink($this->tempPath);
        }
    }

    public function testLoadThrowsForMissingFile(): void
    {
        $loader = new XmlConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $loader->load(sys_get_temp_dir() . '/cloudcastle-di-missing-' . uniqid() . '.xml');
    }

    public function testParseMultipleEntriesInAllListSections(): void
    {
        file_put_contents(
            $this->tempPath,
            '<?xml version="1.0"?><container>'
            . '<bind>'
            . '<binding abstract="a1" concrete="c1"/>'
            . '<binding abstract="a2" concrete="c2"/>'
            . '</bind>'
            . '<autowire>'
            . '<class>' . Clock::class . '</class>'
            . '<class>' . FileLogger::class . '</class>'
            . '</autowire>'
            . '<register_attributes>'
            . '<attribute class="' . CustomServiceIdAttribute::class . '"/>'
            . '<attribute class="' . Clock::class . '"/>'
            . '</register_attributes>'
            . '<scan>'
            . '<directory path="/one"/>'
            . '<directory path="/two" namespace="App"/>'
            . '</scan>'
            . '<tags>'
            . '<tag name="group"><id>one</id><id>two</id></tag>'
            . '</tags>'
            . '</container>',
        );

        $config = (new XmlConfigurationLoader())->load($this->tempPath);
        $bind = $this->assertConfigMap($config, 'bind');
        $scan = $this->assertConfigList($config, 'scan');
        $tags = $this->assertConfigMap($config, 'tags');

        self::assertSame(['a1' => 'c1', 'a2' => 'c2'], $bind);
        self::assertSame([Clock::class, FileLogger::class], $this->assertConfigList($config, 'autowire'));
        self::assertSame(
            [CustomServiceIdAttribute::class, Clock::class],
            $this->assertConfigList($config, 'register_attributes'),
        );
        self::assertCount(2, $scan);
        self::assertSame(['one', 'two'], $tags['group']);
    }

    public function testParseScalarServiceTrimsTextContent(): void
    {
        file_put_contents(
            $this->tempPath,
            '<?xml version="1.0"?><container><services>'
            . '<service id="label">  trimmed-value  </service>'
            . '</services></container>',
        );

        $config = (new XmlConfigurationLoader())->load($this->tempPath);
        $services = $this->assertConfigMap($config, 'services');

        self::assertSame('trimmed-value', $services['label']);
    }

    public function testParseAutowireClassTrimsText(): void
    {
        file_put_contents(
            $this->tempPath,
            '<?xml version="1.0"?><container><autowire>'
            . '<class>  ' . Clock::class . '  </class>'
            . '</autowire></container>',
        );

        self::assertSame([Clock::class], $this->assertConfigList(
            (new XmlConfigurationLoader())->load($this->tempPath),
            'autowire',
        ));
    }

    public function testParseAutowiringAcceptsYesAsTrue(): void
    {
        file_put_contents(
            $this->tempPath,
            '<?xml version="1.0"?><container>'
            . '<autowiring enabled="YES" parameter_name="on"/>'
            . '</container>',
        );

        $autowiring = $this->assertConfigMap(
            (new XmlConfigurationLoader())->load($this->tempPath),
            'autowiring',
        );

        self::assertTrue($autowiring['enabled']);
        self::assertTrue($autowiring['parameter_name']);
    }

    public function testParseServiceWithClassAndLazyTrue(): void
    {
        file_put_contents(
            $this->tempPath,
            '<?xml version="1.0"?><container><services>'
            . '<service id="svc" class="' . FileLogger::class . '" lazy="true" priority="3"/>'
            . '</services></container>',
        );

        $services = $this->assertConfigMap(
            (new XmlConfigurationLoader())->load($this->tempPath),
            'services',
        );

        self::assertSame(
            ['class' => FileLogger::class, 'lazy' => true, 'priority' => 3],
            $services['svc'],
        );
    }
}
