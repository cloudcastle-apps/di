<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationLayer;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationMerger::class)]
final class ConfigurationMergerInvariantsTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    public function testEqualPriorityLaterOrderWinsForMapSection(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => ['key' => 'first']], 0, 10),
            new ConfigurationLayer(['services' => ['key' => 'second']], 1, 10),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('second', $services['key']);
    }

    public function testLowerPriorityDoesNotOverrideHigherPriority(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => ['key' => 'winner']], 0, 100),
            new ConfigurationLayer(['services' => ['key' => 'loser']], 1, 50),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('winner', $services['key']);
    }

    public function testUnwrapEntryUsesExplicitValueWrapper(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['services' => ['key' => ['value' => 'wrapped', 'priority' => 100]]],
                0,
                1,
            ),
            new ConfigurationLayer(['services' => ['key' => 'plain']], 1, 1),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('wrapped', $services['key']);
    }

    public function testInlinePriorityOnMapEntry(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['services' => ['key' => ['priority' => 30, 'class' => FileLogger::class]]],
                0,
                1,
            ),
            new ConfigurationLayer(['services' => ['key' => 'plain']], 1, 5),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame(['class' => FileLogger::class], $services['key']);
    }

    public function testAutowireDuplicateClassHigherPriorityWins(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['autowire' => [['value' => Clock::class, 'priority' => 100]]],
                0,
                null,
            ),
            new ConfigurationLayer(['autowire' => [Clock::class]], 1, null),
        ]);

        self::assertSame([Clock::class], $merged['autowire']);
    }

    public function testRegisterAttributesListPreservesLayerOrder(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['register_attributes' => ['Alpha']], 0, null),
            new ConfigurationLayer(['register_attributes' => ['Beta']], 1, null),
        ]);

        self::assertSame(['Alpha', 'Beta'], $merged['register_attributes']);
    }

    public function testSkipsNonArraySectionInLayer(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => 'invalid'], 0, null),
            new ConfigurationLayer(['services' => ['key' => 'ok']], 1, null),
        ]);

        self::assertSame(['key' => 'ok'], $merged['services']);
    }

    public function testListSectionUsesNumericIndexWhenValueNotString(): void
    {
        $entry = ['factory' => true, 'priority' => 5];

        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['autowire' => [$entry]], 0, null),
            new ConfigurationLayer(['autowire' => [$entry]], 1, 10),
        ]);

        $autowire = $this->assertConfigList($merged, 'autowire');

        self::assertCount(1, $autowire);
        self::assertIsArray($autowire[0]);
        /** @var array<string, mixed> $entry */
        $entry = $autowire[0];
        self::assertSame(['factory' => true], $entry);
    }

    public function testScanDirectoryKeyDedupesSamePath(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(
                ['scan' => [['directory' => '/shared', 'namespace' => 'Old']]],
                0,
                1,
            ),
            new ConfigurationLayer(
                ['scan' => [['directory' => '/shared', 'namespace' => 'New']]],
                1,
                5,
            ),
        ]);

        $scan = $this->assertConfigList($merged, 'scan');

        self::assertCount(1, $scan);
        self::assertIsArray($scan[0]);
        /** @var array<string, mixed> $scanEntry */
        $scanEntry = $scan[0];
        self::assertSame('New', $scanEntry['namespace']);
    }

    public function testAutowireStringKeyUsesSectionPrefix(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['autowire' => [Clock::class]], 0, null),
            new ConfigurationLayer(['autowire' => [FileLogger::class]], 1, null),
        ]);

        self::assertSame([Clock::class, FileLogger::class], $merged['autowire']);
    }

    public function testLayerConfigPriorityOverridesFilePriority(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['priority' => 90, 'services' => ['k' => 'config']], 0, 1),
            new ConfigurationLayer(['services' => ['k' => 'file']], 1, 80),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('config', $services['k']);
    }

    public function testTagsMergeReplacesSameTagName(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['tags' => ['group' => ['a']]], 0, null),
            new ConfigurationLayer(['tags' => ['group' => ['b', 'c']]], 1, null),
        ]);

        $tags = $this->assertConfigMap($merged, 'tags');

        self::assertSame(['b', 'c'], $tags['group']);
    }
}
