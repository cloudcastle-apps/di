<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationLayer;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationMerger::class)]
#[CoversClass(ConfigurationLayer::class)]
final class ConfigurationMergerTest extends TestCase
{
    public function testLastSourceWinsByDefault(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(['services' => ['app.label' => 'first']], 0, null),
            new ConfigurationLayer(['services' => ['app.label' => 'second']], 1, null),
        ]);

        self::assertIsArray($merged['services'] ?? null);
        /** @var array<string, mixed> $services */
        $services = $merged['services'];
        self::assertSame('second', $services['app.label']);
    }

    public function testExplicitParameterPriorityOverridesLaterSource(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(
                ['services' => ['app.label' => ['value' => 'high', 'priority' => 100]]],
                0,
                null,
            ),
            new ConfigurationLayer(['services' => ['app.label' => 'low']], 1, null),
        ]);

        self::assertIsArray($merged['services'] ?? null);
        /** @var array<string, mixed> $services */
        $services = $merged['services'];
        self::assertSame('high', $services['app.label']);
    }

    public function testFilePriorityAppliesToEntriesWithoutOwnPriority(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(['services' => ['app.label' => 'early']], 0, 10),
            new ConfigurationLayer(['services' => ['app.label' => 'late']], 1, 5),
        ]);

        self::assertIsArray($merged['services'] ?? null);
        /** @var array<string, mixed> $services */
        $services = $merged['services'];
        self::assertSame('early', $services['app.label']);
    }

    public function testMergerCombinesListSections(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(['autowire' => ['FirstClass']], 0, null),
            new ConfigurationLayer(['autowire' => ['SecondClass']], 1, null),
            new ConfigurationLayer(['tags' => ['app' => ['a']]], 0, null),
            new ConfigurationLayer(['tags' => ['app' => ['b']]], 1, null),
            new ConfigurationLayer(
                ['register_attributes' => [CustomServiceIdAttribute::class]],
                0,
                null,
            ),
            new ConfigurationLayer(['scan' => [['directory' => '/tmp', 'namespace' => 'App']]], 0, null),
            new ConfigurationLayer(
                ['autowiring' => ['enabled' => false, 'method' => true]],
                1,
                null,
            ),
        ]);

        self::assertIsArray($merged['autowire']);
        /** @var list<mixed> $autowire */
        $autowire = $merged['autowire'];
        self::assertContains('FirstClass', $autowire);
        self::assertContains('SecondClass', $autowire);
        self::assertIsArray($merged['tags']);
        /** @var array<string, list<string>> $tags */
        $tags = $merged['tags'];
        self::assertSame(['b'], $tags['app']);
        self::assertIsArray($merged['register_attributes']);
        /** @var list<string> $registerAttributes */
        $registerAttributes = $merged['register_attributes'];
        self::assertContains(CustomServiceIdAttribute::class, $registerAttributes);
        self::assertIsArray($merged['scan']);
        /** @var list<array{directory: string, namespace?: string}> $scan */
        $scan = $merged['scan'];
        self::assertSame('/tmp', $scan[0]['directory']);
        self::assertIsArray($merged['autowiring']);
        /** @var array<string, mixed> $autowiring */
        $autowiring = $merged['autowiring'];
        self::assertTrue($autowiring['method']);
    }

    public function testMergerRespectsPriorityInsideListEntries(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(
                ['autowire' => [['value' => 'LowPriority', 'priority' => 1]]],
                0,
                null,
            ),
            new ConfigurationLayer(['autowire' => ['HighPriority']], 1, null),
        ]);

        self::assertSame(['LowPriority', 'HighPriority'], $merged['autowire']);
    }

    public function testMergerSkipsNonStringMapKeysAndResolvesComplexListKeys(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(
                [
                    'services' => [
                        123 => 'skipped',
                        'valid.key' => 'value',
                    ],
                    'autowire' => [
                        ['factory' => true],
                    ],
                ],
                0,
                null,
            ),
            new ConfigurationLayer(
                ['services' => ['valid.key' => 'updated']],
                1,
                0,
            ),
        ]);

        self::assertIsArray($merged['services']);
        /** @var array<string, mixed> $services */
        $services = $merged['services'];
        self::assertSame('updated', $services['valid.key']);
        self::assertIsArray($merged['autowire']);
        /** @var list<mixed> $autowire */
        $autowire = $merged['autowire'];
        self::assertCount(1, $autowire);
    }

    public function testMergerUsesScanDirectoryAsListKey(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(
                ['scan' => [['directory' => '/app/src', 'namespace' => 'App\\Old']]],
                0,
                5,
            ),
            new ConfigurationLayer(
                ['scan' => [['directory' => '/app/src', 'namespace' => 'App\\New']]],
                1,
                10,
            ),
        ]);

        self::assertIsArray($merged['scan']);
        /** @var list<array{directory: string, namespace?: string}> $scan */
        $scan = $merged['scan'];
        self::assertCount(1, $scan);
        self::assertSame('App\\New', $scan[0]['namespace'] ?? null);
    }

    public function testMergerUsesConfigPriorityInsideLayer(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(['priority' => 50, 'services' => ['key' => 'from-config']], 0, null),
            new ConfigurationLayer(['services' => ['key' => 'from-order']], 1, null),
        ]);

        self::assertIsArray($merged['services']);
        /** @var array<string, mixed> $services */
        $services = $merged['services'];
        self::assertSame('from-config', $services['key']);
    }

    public function testMergerKeepsHigherPriorityValueForSameKey(): void
    {
        $merger = new ConfigurationMerger();
        $merged = $merger->merge([
            new ConfigurationLayer(['services' => ['shared' => 'winner']], 0, 100),
            new ConfigurationLayer(['services' => ['shared' => 'loser']], 1, 1),
        ]);

        self::assertIsArray($merged['services']);
        /** @var array<string, mixed> $services */
        $services = $merged['services'];
        self::assertSame('winner', $services['shared']);
    }

    public function testMergerMergesContextualRulesByConsumerAndNeed(): void
    {
        $merger = new ConfigurationMerger();
        $report = 'App\\ReportService';
        $audit = 'App\\AuditService';
        $logger = \Psr\Log\LoggerInterface::class;
        $mailer = 'App\\MailerInterface';

        $merged = $merger->merge([
            new ConfigurationLayer([
                'contextual' => [
                    $report => [$logger => 'log.file'],
                    $audit => [$logger => 'log.audit'],
                ],
            ], 0, null),
            new ConfigurationLayer([
                'contextual' => [
                    $report => [$mailer => 'mail.smtp', $logger => 'log.memory'],
                ],
            ], 1, null),
        ]);

        self::assertIsArray($merged['contextual']);
        /** @var array<string, array<string, string>> $contextual */
        $contextual = $merged['contextual'];
        self::assertSame('log.memory', $contextual[$report][$logger]);
        self::assertSame('mail.smtp', $contextual[$report][$mailer]);
        self::assertSame('log.audit', $contextual[$audit][$logger]);
    }

    public function testMergerContextualRespectsExplicitGivePriority(): void
    {
        $merger = new ConfigurationMerger();
        $consumer = 'App\\ReportService';
        $need = \Psr\Log\LoggerInterface::class;

        $merged = $merger->merge([
            new ConfigurationLayer([
                'contextual' => [
                    $consumer => [
                        $need => ['value' => 'log.high', 'priority' => 100],
                    ],
                ],
            ], 0, null),
            new ConfigurationLayer([
                'contextual' => [
                    $consumer => [$need => 'log.low'],
                ],
            ], 1, null),
        ]);

        self::assertIsArray($merged['contextual']);
        /** @var array<string, array<string, string>> $contextual */
        $contextual = $merged['contextual'];
        self::assertSame('log.high', $contextual[$consumer][$need]);
    }

    public function testMergerIgnoresInvalidContextualEntries(): void
    {
        $merger = new ConfigurationMerger();
        $consumer = ReportService::class;
        $need = \Psr\Log\LoggerInterface::class;

        $merged = $merger->merge([
            new ConfigurationLayer([
                'contextual' => [
                    123 => [$need => 'skip.invalid-consumer'],
                    $consumer => 'not-a-needs-map',
                    'App\\Orphan' => [
                        456 => 'skip.invalid-need',
                        $need => ['invalid' => 'structure'],
                    ],
                ],
            ], 0, null),
            new ConfigurationLayer(['contextual' => 'not-an-array'], 1, null),
        ]);

        self::assertSame([], $merged['contextual']);
    }

    public function testMergerSkipsInvalidContextualLayerButProcessesFollowingLayer(): void
    {
        $consumer = ReportService::class;
        $need = \Psr\Log\LoggerInterface::class;

        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['contextual' => 'not-an-array'], 0, null),
            new ConfigurationLayer([
                'contextual' => [
                    $consumer => [$need => 'log.memory'],
                ],
            ], 1, null),
        ]);

        self::assertIsArray($merged['contextual']);
        /** @var array<string, array<string, string>> $contextual */
        $contextual = $merged['contextual'];
        self::assertSame('log.memory', $contextual[$consumer][$need]);
    }
}
