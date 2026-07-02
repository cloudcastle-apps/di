<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationApplicator;
use CloudCastle\DI\Configuration\ConfigurationLoaderRegistry;
use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\YamlConfigurationLoader;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationApplicator::class)]
#[CoversClass(ContainerConfigurator::class)]
#[CoversClass(ConfigurationLoaderRegistry::class)]
final class ConfigurationApplicatorBehaviorTest extends TestCase
{
    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testApplyScanPassesNullNamespaceWhenMissing(): void
    {
        $container = new Container();
        $scanDirectory = \dirname(__DIR__, 2) . '/Fixtures/Autowire';

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                ['directory' => $scanDirectory],
            ],
        ]);

        self::assertTrue($container->hasDefinition(Clock::class));
    }

    public function testApplyScanUsesExplicitNamespace(): void
    {
        $container = new Container();
        $scanDirectory = \dirname(__DIR__, 2) . '/Fixtures/Autowire';

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                [
                    'directory' => $scanDirectory,
                    'namespace' => 'CloudCastle\\DI\\Tests\\Fixtures\\Autowire',
                ],
            ],
        ]);

        self::assertTrue($container->hasDefinition(Clock::class));
    }

    public function testApplyServiceWithLazyFalseUsesBindWhenIdDiffersFromClass(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        (new ConfigurationApplicator())->apply($container, [
            'services' => [
                'custom.logger' => ['class' => FileLogger::class, 'lazy' => false],
            ],
        ]);

        self::assertInstanceOf(FileLogger::class, $container->get('custom.logger'));
    }

    public function testApplyServiceWithSameIdAsClassUsesAutowire(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        (new ConfigurationApplicator())->apply($container, [
            'services' => [
                Clock::class => ['class' => Clock::class],
            ],
        ]);

        self::assertInstanceOf(Clock::class, $container->get(Clock::class));
    }

    public function testApplyAutowiringFlagsStayDisabledWhenNotTrue(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => [
                'enabled' => false,
                'parameter_name' => false,
                'property' => false,
                'method' => false,
            ],
        ]);

        self::assertFalse($container->isAutowiringEnabled());
        self::assertFalse($container->isParameterNameAutowiringEnabled());
        self::assertFalse($container->isPropertyAutowiringEnabled());
        self::assertFalse($container->isMethodAutowiringEnabled());
    }

    public function testApplyAutowiringDoesNotEnableWhenEnabledKeyIsOmitted(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => [
                'parameter_name' => true,
            ],
        ]);

        self::assertFalse($container->isAutowiringEnabled());
        self::assertTrue($container->isParameterNameAutowiringEnabled());
    }

    public function testContainerConfiguratorUsesCustomLoaderRegistry(): void
    {
        $registry = new ConfigurationLoaderRegistry([new JsonConfigurationLoader()]);
        $configurator = new ContainerConfigurator($registry);
        $container = new Container();

        $configurator->configure($container, [$this->fixturesDirectory . '/override.json']);

        self::assertSame('from-json', $container->get('app.label'));
    }

    public function testConfigurationLoaderRegistryCreateDefaultLoaders(): void
    {
        $loaders = ConfigurationLoaderRegistry::createDefaultLoaders();

        self::assertCount(4, $loaders);
        self::assertInstanceOf(PhpConfigurationLoader::class, $loaders[0]);
        self::assertInstanceOf(JsonConfigurationLoader::class, $loaders[1]);
        self::assertInstanceOf(YamlConfigurationLoader::class, $loaders[2]);
    }

    public function testContainerConfiguratorLoadReturnsMergedConfig(): void
    {
        $configurator = new ContainerConfigurator();
        $config = $configurator->load($this->fixturesDirectory . '/bind.php');

        self::assertIsArray($config['bind'] ?? null);
    }

    public function testAutowiringEnabledOnlyWhenFlagStrictlyTrue(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['enabled' => 1],
        ]);

        self::assertFalse($container->isAutowiringEnabled());

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['enabled' => true],
        ]);

        self::assertTrue($container->isAutowiringEnabled());
    }
}
