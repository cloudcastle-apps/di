<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationApplicator;
use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Container;
use CloudCastle\DI\TaggedServiceLocator;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationApplicator::class)]
final class ConfigurationApplicatorTest extends TestCase
{
    private string $fixturesDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testApplySkipsMalformedConfigurationSections(): void
    {
        $container = new Container();
        $applicator = new ConfigurationApplicator();

        $applicator->apply($container, [
            'register_attributes' => [123],
            'autowiring' => 'invalid',
            'scan' => [
                ['directory' => 123],
                ['namespace' => 'OnlyNamespace'],
            ],
            'services' => [
                123 => 'skipped',
                FileLogger::class => ['class' => FileLogger::class],
            ],
            'tags' => [
                123 => 'invalid',
            ],
        ]);

        self::assertTrue($container->has(FileLogger::class));
    }

    public function testApplyTagsServicesFromConfiguration(): void
    {
        $container = new Container();
        $container->set('tagged.service', 'value');

        (new ConfigurationApplicator())->apply($container, [
            'tags' => [
                'group' => ['tagged.service', 123],
            ],
        ]);

        $locator = new TaggedServiceLocator($container, 'group');

        self::assertCount(1, iterator_to_array($locator));
    }

    public function testApplyFullPhpConfiguration(): void
    {
        $container = new Container();
        $configurator = new ContainerConfigurator();

        $configurator->configure($container, [$this->fixturesDirectory . '/full.php']);

        self::assertTrue($container->get('app.flag'));
        self::assertTrue($container->has('lazy.logger'));
        self::assertTrue($container->isAutowiringEnabled());
        self::assertInstanceOf(FileLogger::class, $container->get('config.bind.abstract'));
    }

    public function testApplyMethodAcceptsMergedConfigDirectly(): void
    {
        $container = new Container();
        $configurator = new ContainerConfigurator();
        $config = $configurator->load($this->fixturesDirectory . '/bind.php');

        $configurator->apply($container, $config);

        self::assertInstanceOf(FileLogger::class, $container->get(LoggerInterface::class));
    }
}
