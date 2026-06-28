<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationApplicator;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(ConfigurationApplicator::class)]
final class ConfigurationApplicatorFilteringTest extends TestCase
{
    public function testApplySkipsInvalidRegisterAttributeEntries(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'register_attributes' => [123, null],
        ]);

        self::assertFalse($container->isAutowiringEnabled());
    }

    public function testApplySkipsInvalidAutowireEntries(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        (new ConfigurationApplicator())->apply($container, [
            'autowire' => [Clock::class, 99],
        ]);

        self::assertTrue($container->hasDefinition(Clock::class));
        self::assertFalse($container->hasDefinition('99'));
    }

    public function testApplySkipsInvalidBindEntries(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        (new ConfigurationApplicator())->apply($container, [
            'bind' => [
                1 => FileLogger::class,
                'valid' => 2,
                'logger.ok' => FileLogger::class,
            ],
        ]);

        self::assertFalse($container->hasDefinition('1'));
        self::assertFalse($container->hasDefinition('valid'));
        self::assertInstanceOf(FileLogger::class, $container->get('logger.ok'));
    }

    public function testApplySkipsInvalidAliasEntries(): void
    {
        $container = new Container();
        $container->set('target', 'value');

        (new ConfigurationApplicator())->apply($container, [
            'aliases' => [
                1 => 'target',
                'bad' => 2,
                'good' => 'target',
            ],
        ]);

        self::assertFalse($container->has('1'));
        self::assertFalse($container->has('bad'));
        self::assertSame('value', $container->get('good'));
    }

    public function testApplySkipsInvalidTagEntries(): void
    {
        $container = new Container();
        $container->set('handler', 'ok');

        (new ConfigurationApplicator())->apply($container, [
            'tags' => [
                99 => ['handler'],
                'handlers' => ['handler', 123],
                'valid' => 'not-array',
            ],
        ]);

        self::assertSame([], $container->getTagged('99'));
        self::assertSame(['handler' => 'ok'], $container->getTagged('handlers'));
        self::assertSame([], $container->getTagged('valid'));
    }

    public function testApplySkipsScanWithoutDirectory(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                ['namespace' => 'App'],
                ['directory' => 123],
            ],
        ]);

        self::assertFalse($container->isAutowiringEnabled());
    }

    public function testApplySkipsNonArrayScanEntries(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                'invalid-entry',
                123,
            ],
        ]);

        self::assertFalse($container->isAutowiringEnabled());
    }

    public function testApplyIgnoresNonArrayAutowiringSection(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => 'enabled',
        ]);

        self::assertFalse($container->isAutowiringEnabled());
    }

    public function testApplyIgnoresNonArraySectionRoots(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'services' => 'invalid',
            'bind' => false,
            'aliases' => 1,
            'autowire' => null,
            'tags' => 'x',
            'register_attributes' => 0,
            'scan' => 'dir',
        ]);

        self::assertFalse($container->hasDefinition('invalid'));
    }

    public function testApplySkipsInvalidContextualNeedButProcessesFollowingRules(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'contextual' => [
                ReportService::class => [
                    456 => 'skip.invalid-need',
                    LoggerInterface::class => 'memory.logger',
                    'App\\Contracts\\SecondaryPort' => 'port.impl',
                ],
            ],
        ]);

        self::assertSame(
            'memory.logger',
            $container->contextualGive(ReportService::class, LoggerInterface::class),
        );
        self::assertSame(
            'port.impl',
            $container->contextualGive(ReportService::class, 'App\\Contracts\\SecondaryPort'),
        );
    }

    public function testApplySkipsInvalidContextualGiveButProcessesFollowingRules(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'contextual' => [
                ReportService::class => [
                    LoggerInterface::class => 789,
                    'App\\Contracts\\SecondaryPort' => 'port.impl',
                ],
            ],
        ]);

        self::assertNull($container->contextualGive(ReportService::class, LoggerInterface::class));
        self::assertSame(
            'port.impl',
            $container->contextualGive(ReportService::class, 'App\\Contracts\\SecondaryPort'),
        );
    }

    public function testApplySkipsInvalidContextualConsumerButProcessesFollowingConsumer(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'contextual' => [
                123 => [LoggerInterface::class => 'skip.invalid-consumer'],
                ReportService::class => [
                    LoggerInterface::class => 'memory.logger',
                ],
            ],
        ]);

        self::assertSame(
            'memory.logger',
            $container->contextualGive(ReportService::class, LoggerInterface::class),
        );
    }
}
