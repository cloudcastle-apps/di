<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\YamlConfigurationLoader;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\AuditService;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\MemoryLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerConfigurator::class)]
#[CoversClass(YamlConfigurationLoader::class)]
#[CoversClass(XmlConfigurationLoader::class)]
final class ContextualConfigurationLoaderTest extends TestCase
{
    private string $fixturesDirectory;

    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__, 2) . '/Fixtures/Config';
    }

    public function testYamlContextualConfigurationAppliesWhenNeedsGive(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [$this->fixturesDirectory . '/contextual.yaml']);

        $report = $container->get(ReportService::class);
        $audit = $container->get(AuditService::class);

        self::assertInstanceOf(MemoryLogger::class, $report->logger);
        self::assertInstanceOf(FileLogger::class, $audit->logger);
    }

    public function testXmlContextualConfigurationAppliesWhenNeedsGive(): void
    {
        $container = new Container();
        (new ContainerConfigurator())->configure($container, [$this->fixturesDirectory . '/contextual.xml']);

        $report = $container->get(ReportService::class);
        $audit = $container->get(AuditService::class);

        self::assertInstanceOf(ReportService::class, $report);
        self::assertInstanceOf(AuditService::class, $audit);
        self::assertInstanceOf(MemoryLogger::class, $report->logger);
        self::assertInstanceOf(FileLogger::class, $audit->logger);
    }
}
