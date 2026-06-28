<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationLayer;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(ConfigurationMerger::class)]
final class ConfigurationMergerMutationTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    public function testScanInlineEntryPriorityBeatsLayerFilePriority(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['scan' => [['directory' => '/shared', 'namespace' => 'Old', 'priority' => 50]]],
                0,
                10,
            ),
            new ConfigurationLayer(
                ['scan' => [['directory' => '/shared', 'namespace' => 'New']]],
                1,
                20,
            ),
        ]);

        $scan = $this->assertConfigList($merged, 'scan');
        self::assertIsArray($scan[0]);
        /** @var array<string, mixed> $scanEntry */
        $scanEntry = $scan[0];
        self::assertSame('Old', $scanEntry['namespace']);
    }

    public function testAutowireArrayWithDirectoryDoesNotUseScanMergeKey(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['autowire' => [['directory' => '/one'], ['directory' => '/two']]],
                0,
                null,
            ),
        ]);

        self::assertCount(2, $this->assertConfigList($merged, 'autowire'));
    }

    public function testAutowireNonStringValueUsesIndexInKey(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['autowire' => [['marker' => 'a'], ['marker' => 'b']]],
                0,
                null,
            ),
        ]);

        self::assertCount(2, $this->assertConfigList($merged, 'autowire'));
    }

    public function testAutowireListEntryKeyIncludesSectionPrefix(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['autowire' => [Clock::class]], 0, null),
            new ConfigurationLayer(['register_attributes' => [Clock::class]], 0, null),
        ]);

        self::assertSame([Clock::class], $merged['autowire']);
        self::assertSame([Clock::class], $merged['register_attributes']);
    }

    public function testMapSectionSkipsNonStringKeys(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => [99 => 'skip', 'keep' => 'value']], 0, null),
        ]);

        self::assertSame(['keep' => 'value'], $merged['services']);
    }

    public function testFirstWinnerStoredWhenKeyWasMissing(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => ['only' => 'value']], 0, null),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('value', $services['only']);
    }

    public function testHigherPriorityCandidateReplacesExistingWinner(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => ['id' => 'low']], 0, 1),
            new ConfigurationLayer(['services' => ['id' => 'high']], 1, 100),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('high', $services['id']);
    }

    public function testSamePriorityUsesHigherLayerOrder(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['bind' => ['Contract' => 'first']], 0, 5),
            new ConfigurationLayer(['bind' => ['Contract' => 'second']], 1, 5),
        ]);

        $bind = $this->assertConfigMap($merged, 'bind');

        self::assertSame('second', $bind['Contract']);
    }

    public function testListResultSortedByLayerOrder(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['autowire' => [FileLogger::class]], 1, null),
            new ConfigurationLayer(['autowire' => [Clock::class]], 0, null),
        ]);

        self::assertSame([Clock::class, FileLogger::class], $merged['autowire']);
    }

    public function testInlinePriorityStrippedFromMergedMapValue(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['services' => ['svc' => ['priority' => 10, 'class' => FileLogger::class]]],
                0,
                null,
            ),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame(['class' => FileLogger::class], $services['svc']);
    }

    public function testInlinePriorityOnScalarEntryIsApplied(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['services' => ['label' => ['value' => 'high', 'priority' => 100]]],
                0,
                1,
            ),
            new ConfigurationLayer(['services' => ['label' => 'low']], 1, 50),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('high', $services['label']);
    }

    public function testNonArraySectionDataIsIgnored(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['bind' => 'invalid'], 0, null),
            new ConfigurationLayer(['bind' => ['A' => 'a']], 1, null),
        ]);

        self::assertSame(['A' => 'a'], $merged['bind']);
    }

    public function testListEntryUsesLayerDefaultWhenPriorityMissing(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['autowire' => [Clock::class]], 0, 100),
            new ConfigurationLayer(['autowire' => [FileLogger::class]], 1, 1),
        ]);

        self::assertSame([Clock::class, FileLogger::class], $merged['autowire']);
    }

    public function testExplicitEntryPriorityOverridesLayerDefault(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['services' => ['key' => ['value' => 'winner', 'priority' => 100]]],
                0,
                1,
            ),
            new ConfigurationLayer(['services' => ['key' => 'loser']], 1, 50),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('winner', $services['key']);
    }

    public function testDuplicateListKeySamePriorityAndOrderKeepsFirstValue(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                [
                    'scan' => [
                        ['directory' => '/shared', 'namespace' => 'First'],
                        ['directory' => '/shared', 'namespace' => 'Second'],
                    ],
                ],
                0,
                10,
            ),
        ]);

        $scan = $this->assertConfigList($merged, 'scan');
        self::assertIsArray($scan[0]);
        /** @var array<string, mixed> $scanEntry */
        $scanEntry = $scan[0];
        self::assertSame('First', $scanEntry['namespace']);
    }

    public function testEqualPriorityDoesNotReplaceWithoutHigherLayerOrder(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => ['id' => 'first']], 5, 10),
            new ConfigurationLayer(['services' => ['id' => 'second']], 3, 10),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('first', $services['id']);
    }

    public function testMergerSkipsInvalidContextualNeedButProcessesFollowingRules(): void
    {
        $consumer = ReportService::class;
        $logger = LoggerInterface::class;
        $secondaryNeed = 'App\\Contracts\\SecondaryPort';

        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer([
                'contextual' => [
                    $consumer => [
                        456 => 'skip.invalid-need',
                        $logger => 'log.memory',
                        $secondaryNeed => 'port.impl',
                    ],
                ],
            ], 0, null),
        ]);

        $contextual = $this->assertConfigMap($merged, 'contextual');
        $needsMap = $this->assertConfigMap($contextual, $consumer);

        self::assertSame('log.memory', $needsMap[$logger]);
        self::assertSame('port.impl', $needsMap[$secondaryNeed]);
    }

    public function testMergerSkipsInvalidContextualGiveButProcessesFollowingRules(): void
    {
        $consumer = ReportService::class;
        $logger = LoggerInterface::class;
        $secondaryNeed = 'App\\Contracts\\SecondaryPort';

        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer([
                'contextual' => [
                    $consumer => [
                        $logger => ['invalid' => 'structure'],
                        $secondaryNeed => 'port.impl',
                    ],
                ],
            ], 0, null),
        ]);

        $contextual = $this->assertConfigMap($merged, 'contextual');
        $needsMap = $this->assertConfigMap($contextual, $consumer);

        self::assertArrayNotHasKey($logger, $needsMap);
        self::assertSame('port.impl', $needsMap[$secondaryNeed]);
    }

    public function testMergerSkipsInvalidContextualConsumerButProcessesFollowingConsumer(): void
    {
        $consumer = ReportService::class;
        $logger = LoggerInterface::class;

        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer([
                'contextual' => [
                    123 => [$logger => 'skip.invalid-consumer'],
                    $consumer => [
                        $logger => 'log.memory',
                    ],
                ],
            ], 0, null),
        ]);

        $contextual = $this->assertConfigMap($merged, 'contextual');
        $needsMap = $this->assertConfigMap($contextual, $consumer);

        self::assertSame('log.memory', $needsMap[$logger]);
    }
}
