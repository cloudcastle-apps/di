<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationApplicator;
use CloudCastle\DI\Container;
use CloudCastle\DI\LazyService;
use CloudCastle\DI\TaggedServiceLocator;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\AuditService;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\MemoryLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationApplicator::class)]
final class ConfigurationApplicatorSectionsTest extends TestCase
{
    private string $scanDirectory;

    protected function setUp(): void
    {
        $this->scanDirectory = \dirname(__DIR__, 2) . '/Fixtures/Autowire';
    }

    public function testApplyRegisterAttributesEnablesCustomAttributeInjection(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->enablePropertyAutowiring();

        $expectedClock = new Clock();
        $container->set('app.clock', $expectedClock);

        (new ConfigurationApplicator())->apply($container, [
            'register_attributes' => [CustomServiceIdAttribute::class],
            'autowire' => [CustomAttributePropertyService::class],
        ]);

        $service = $container->get(CustomAttributePropertyService::class);
        self::assertInstanceOf(CustomAttributePropertyService::class, $service);
        self::assertSame($expectedClock, $service->getClock());
    }

    public function testApplyAutowiringEnablesAllModes(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => [
                'enabled' => true,
                'parameter_name' => true,
                'property' => true,
                'method' => true,
            ],
        ]);

        self::assertTrue($container->isAutowiringEnabled());
        self::assertTrue($container->isParameterNameAutowiringEnabled());
        self::assertTrue($container->isPropertyAutowiringEnabled());
        self::assertTrue($container->isMethodAutowiringEnabled());
    }

    public function testApplyScanRegistersClassesFromDirectory(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                ['directory' => $this->scanDirectory],
                ['directory' => $this->scanDirectory, 'namespace' => 'CloudCastle\\DI\\Tests\\Fixtures\\Autowire'],
            ],
        ]);

        self::assertTrue($container->has(Clock::class));
    }

    public function testApplyServicesSupportsLazyScalarAndClassBinding(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'services' => [
                'app.mode' => 'production',
                'lazy.logger' => ['class' => FileLogger::class, 'lazy' => true],
                FileLogger::class => ['class' => FileLogger::class],
                'logger.alias' => ['class' => FileLogger::class],
            ],
        ]);

        self::assertSame('production', $container->get('app.mode'));
        self::assertInstanceOf(LazyService::class, $container->get('lazy.logger'));
        self::assertInstanceOf(FileLogger::class, $container->get(FileLogger::class));
        self::assertInstanceOf(FileLogger::class, $container->get('logger.alias'));
    }

    public function testApplyBindAliasesAutowireListAndTags(): void
    {
        $container = new Container();
        $container->set('handler.one', 'first');
        $container->set('handler.two', 'second');

        (new ConfigurationApplicator())->apply($container, [
            'bind' => ['logger.contract' => FileLogger::class],
            'aliases' => ['mode' => 'app.mode'],
            'autowire' => [Clock::class],
            'tags' => [
                'handlers' => ['handler.one', 'handler.two'],
            ],
            'services' => ['app.mode' => 'runtime'],
        ]);

        self::assertInstanceOf(FileLogger::class, $container->get('logger.contract'));
        self::assertSame('runtime', $container->get('mode'));
        self::assertInstanceOf(Clock::class, $container->get(Clock::class));

        $locator = new TaggedServiceLocator($container, 'handlers');
        self::assertCount(2, iterator_to_array($locator));
    }

    public function testApplyContextualRegistersWhenNeedsGiveRules(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->set('memory.logger', new MemoryLogger());
        $container->set('default.logger', new FileLogger());
        $container->bind(LoggerInterface::class, 'default.logger');

        (new ConfigurationApplicator())->apply($container, [
            'contextual' => [
                ReportService::class => [
                    LoggerInterface::class => 'memory.logger',
                ],
            ],
            'autowire' => [ReportService::class, AuditService::class],
        ]);

        $report = $container->get(ReportService::class);
        $audit = $container->get(AuditService::class);

        self::assertInstanceOf(ReportService::class, $report);
        self::assertInstanceOf(AuditService::class, $audit);
        self::assertInstanceOf(MemoryLogger::class, $report->logger);
        self::assertInstanceOf(FileLogger::class, $audit->logger);
        self::assertSame(
            'memory.logger',
            $container->contextualGive(ReportService::class, LoggerInterface::class),
        );
    }

    public function testApplyContextualIgnoresInvalidEntries(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'contextual' => [
                123 => [LoggerInterface::class => 'skip'],
                ReportService::class => 'not-a-needs-map',
                AuditService::class => [
                    456 => 'skip',
                    LoggerInterface::class => 789,
                ],
            ],
        ]);

        self::assertNull($container->contextualGive(ReportService::class, LoggerInterface::class));
    }
}
