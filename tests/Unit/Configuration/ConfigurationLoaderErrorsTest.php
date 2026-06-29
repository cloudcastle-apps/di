<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpConfigurationLoader::class)]
#[CoversClass(JsonConfigurationLoader::class)]
#[CoversClass(XmlConfigurationLoader::class)]
final class ConfigurationLoaderErrorsTest extends TestCase
{
    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testJsonLoaderThrowsForMissingFile(): void
    {
        $loader = new JsonConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $loader->load($this->fixturesDirectory . '/missing.json');
    }

    public function testJsonLoaderThrowsWhenRootIsNotObject(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-null.json';
        file_put_contents($path, 'null');

        try {
            $loader = new JsonConfigurationLoader();

            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('должна быть объектом');

            $loader->load($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testPhpLoaderThrowsWhenFileReturnsNonArray(): void
    {
        $loader = new PhpConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('должна возвращать массив');

        $loader->load($this->fixturesDirectory . '/invalid.php');
    }

    public function testJsonLoaderThrowsForInvalidJson(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-invalid.json';
        file_put_contents($path, '{invalid');

        try {
            $loader = new JsonConfigurationLoader();

            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('Ошибка разбора JSON');

            $loader->load($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testJsonLoaderAcceptsPayloadAtMaxDepth(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-max-depth.json';
        file_put_contents($path, str_repeat('{"n":', 511) . '1' . str_repeat('}', 511));

        try {
            $config = (new JsonConfigurationLoader())->load($path);

            self::assertIsArray($config);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testXmlLoaderThrowsForMissingRequiredAttribute(): void
    {
        $loader = new XmlConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('обязательный атрибут');

        $loader->load($this->fixturesDirectory . '/broken-service.xml');
    }

    public function testXmlLoaderThrowsForInvalidPriorityAttribute(): void
    {
        $loader = new XmlConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('должен быть числом');

        $loader->load($this->fixturesDirectory . '/invalid-priority.xml');
    }

    public function testXmlLoaderThrowsForInvalidXmlFile(): void
    {
        $path = sys_get_temp_dir() . '/cloudcastle-di-broken.xml';
        file_put_contents($path, '<container><unclosed>');

        try {
            $loader = new XmlConfigurationLoader();

            $this->expectException(ContainerException::class);
            $this->expectExceptionMessage('Ошибка разбора XML');

            $loader->load($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testPhpLoaderThrowsForMissingFile(): void
    {
        $loader = new PhpConfigurationLoader();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $loader->load($this->fixturesDirectory . '/missing.php');
    }
}
