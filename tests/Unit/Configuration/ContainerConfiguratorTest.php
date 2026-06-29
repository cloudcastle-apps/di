<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationDirectoryScan;
use CloudCastle\DI\Configuration\ConfigurationDirectorySource;
use CloudCastle\DI\Configuration\ConfigurationFilesSource;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use CloudCastle\DI\Configuration\ConfigurationSource;
use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(ContainerConfigurator::class)]
#[CoversClass(ConfigurationMerger::class)]
#[CoversClass(PhpConfigurationLoader::class)]
#[CoversClass(JsonConfigurationLoader::class)]
#[CoversClass(XmlConfigurationLoader::class)]
final class ContainerConfiguratorTest extends TestCase
{
    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testApplyMethodIsPublic(): void
    {
        $method = new ReflectionMethod(ContainerConfigurator::class, 'apply');

        self::assertTrue($method->isPublic());
    }

    public function testConfigureFromPhpFile(): void
    {
        $container = new Container();
        $configurator = new ContainerConfigurator();

        $configurator->configure($container, [$this->fixturesDirectory . '/base.php']);

        self::assertSame('test', $container->get('app.env'));
        self::assertSame('from-php', $container->get('app.label'));
        self::assertSame('test', $container->get('env'));
    }

    public function testLoadReturnsParsedArrayWithoutApplying(): void
    {
        $configurator = new ContainerConfigurator();
        $config = $configurator->load($this->fixturesDirectory . '/base.php');

        self::assertIsArray($config['services'] ?? null);
    }

    public function testConfigureMergesMultipleSourcesWithLastWins(): void
    {
        $container = new Container();
        $configurator = new ContainerConfigurator();

        $configurator->configure($container, [
            $this->fixturesDirectory . '/base.php',
            $this->fixturesDirectory . '/override.json',
        ]);

        self::assertSame('test', $container->get('app.env'));
        self::assertSame('from-json', $container->get('app.label'));
        self::assertSame(30, $container->get('app.timeout'));
        self::assertSame(30, $container->get('timeout'));
    }

    public function testConfigureRespectsParameterPriorityOverLoadOrder(): void
    {
        $container = new Container();
        $configurator = new ContainerConfigurator();

        $configurator->configure($container, [
            $this->fixturesDirectory . '/override.json',
            $this->fixturesDirectory . '/priority.xml',
        ]);

        self::assertSame('from-xml', $container->get('app.label'));
        self::assertSame('runtime', $container->get('app.mode'));
        self::assertTrue($container->isAutowiringEnabled());
    }

    public function testConfigureFromPhpWithBind(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [$this->fixturesDirectory . '/bind.php']);

        self::assertInstanceOf(FileLogger::class, $container->get(LoggerInterface::class));
    }

    public function testConfigurationSourceAppliesFilePriority(): void
    {
        $container = new Container();
        $configurator = new ContainerConfigurator();

        $configurator->configure($container, [
            new ConfigurationSource($this->fixturesDirectory . '/base.php', 100),
            $this->fixturesDirectory . '/override.json',
        ]);

        self::assertSame('from-php', $container->get('app.label'));
    }

    public function testLoadManyReturnsMergedConfigurationWithoutApplying(): void
    {
        $config = (new ContainerConfigurator())->loadMany([
            $this->fixturesDirectory . '/base.php',
            $this->fixturesDirectory . '/override.json',
        ]);

        self::assertIsArray($config['services']);
        /** @var array<string, mixed> $services */
        $services = $config['services'];
        self::assertSame('from-json', $services['app.label']);
        self::assertSame(30, $services['app.timeout']);
    }

    public function testConfigureFromDirectoryPathString(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [$this->fixturesDirectory . '/layers']);

        self::assertSame('from-layer-json', $container->get('app.label'));
        self::assertSame(15, $container->get('app.timeout'));
    }

    public function testConfigureFromConfigurationDirectorySource(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [
            new ConfigurationDirectorySource($this->fixturesDirectory . '/layers'),
        ]);

        self::assertSame('from-layer-json', $container->get('app.label'));
    }

    public function testConfigureFromConfigurationFilesSource(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [
            new ConfigurationFilesSource([
                $this->fixturesDirectory . '/base.php',
                $this->fixturesDirectory . '/override.json',
            ]),
        ]);

        self::assertSame('from-json', $container->get('app.label'));
        self::assertSame(30, $container->get('app.timeout'));
    }

    public function testConfigureFromMultipleDirectories(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [
            new ConfigurationDirectorySource($this->fixturesDirectory . '/layers'),
            new ConfigurationDirectorySource(
                $this->fixturesDirectory . '/nested',
                scan: ConfigurationDirectoryScan::Recursive,
            ),
        ]);

        self::assertSame('nested-root', $container->get('app.mode'));
        self::assertSame(15, $container->get('app.timeout'));
        self::assertSame('from-nested-json', $container->get('app.label'));
    }

    public function testDirectorySourcePriorityOverridesLaterFileWithoutPriority(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [
            $this->fixturesDirectory . '/override.json',
            new ConfigurationDirectorySource($this->fixturesDirectory . '/layers', priority: 100),
        ]);

        self::assertSame('from-layer-json', $container->get('app.label'));
    }
}
