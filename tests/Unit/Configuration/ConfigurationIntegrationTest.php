<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationApplicator;
use CloudCastle\DI\Configuration\ConfigurationLayer;
use CloudCastle\DI\Configuration\ConfigurationLoaderRegistry;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use CloudCastle\DI\Configuration\ConfigurationSource;
use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\YamlConfigurationLoader;
use CloudCastle\DI\Container;
use CloudCastle\DI\LazyService;
use CloudCastle\DI\TaggedServiceLocator;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationApplicator::class)]
#[CoversClass(ConfigurationMerger::class)]
#[CoversClass(ContainerConfigurator::class)]
#[CoversClass(ConfigurationLoaderRegistry::class)]
#[CoversClass(ConfigurationSource::class)]
#[CoversClass(PhpConfigurationLoader::class)]
#[CoversClass(JsonConfigurationLoader::class)]
#[CoversClass(XmlConfigurationLoader::class)]
#[CoversClass(YamlConfigurationLoader::class)]
final class ConfigurationIntegrationTest extends TestCase
{
    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testFullPhpConfigurationAppliesAllSections(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [$this->fixturesDirectory . '/full.php']);

        self::assertTrue($container->get('app.flag'));
        self::assertTrue($container->get('flag'));
        self::assertTrue($container->isAutowiringEnabled());
        self::assertTrue($container->isParameterNameAutowiringEnabled());
        self::assertTrue($container->isPropertyAutowiringEnabled());
        self::assertTrue($container->isMethodAutowiringEnabled());
        self::assertInstanceOf(LazyService::class, $container->get('lazy.logger'));
        self::assertInstanceOf(FileLogger::class, $container->get('config.bind.abstract'));
        self::assertInstanceOf(FileLogger::class, $container->get(FileLogger::class));
        self::assertInstanceOf(Clock::class, $container->get('app.clock'));
        $customService = $container->get(CustomAttributePropertyService::class);
        self::assertInstanceOf(CustomAttributePropertyService::class, $customService);
        self::assertSame($container->get('app.clock'), $customService->getClock());

        $locator = new TaggedServiceLocator($container, 'logger');
        self::assertCount(1, iterator_to_array($locator));
    }

    public function testMultipleSourcesRespectPriorityAndLoadOrder(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [
            $this->fixturesDirectory . '/base.php',
            $this->fixturesDirectory . '/override.json',
            $this->fixturesDirectory . '/priority.xml',
            new ConfigurationSource($this->fixturesDirectory . '/full.php', 1),
        ]);

        self::assertSame('from-xml', $container->get('app.label'));
        self::assertSame(30, $container->get('app.timeout'));
        self::assertSame('test', $container->get('app.env'));
        self::assertSame('runtime', $container->get('mode'));
    }

    public function testComprehensiveXmlConfigurationIsApplied(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [$this->fixturesDirectory . '/comprehensive.xml']);

        self::assertSame('configured', $container->get('xml.scalar'));
        self::assertTrue($container->has('xml.handler'));
        self::assertInstanceOf(LazyService::class, $container->get('xml.handler'));
        self::assertTrue($container->has('handler'));
        self::assertInstanceOf(FileLogger::class, $container->get('xml.bind'));
        self::assertInstanceOf(FileLogger::class, $container->get(FileLogger::class));
        self::assertTrue($container->isAutowiringEnabled());
        self::assertTrue($container->isParameterNameAutowiringEnabled());
        self::assertTrue($container->isPropertyAutowiringEnabled());
        self::assertTrue($container->isMethodAutowiringEnabled());

        $locator = new TaggedServiceLocator($container, 'handlers');
        self::assertCount(1, iterator_to_array($locator));
    }

    public function testMergerUsesLaterSourceOnEqualFilePriority(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(['services' => ['shared' => 'first']], 0, 10),
            new ConfigurationLayer(['services' => ['shared' => 'second']], 1, 10),
        ]);

        self::assertIsArray($merged['services']);
        /** @var array<string, mixed> $services */
        $services = $merged['services'];
        self::assertSame('second', $services['shared']);
    }
}
