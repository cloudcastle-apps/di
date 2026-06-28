<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationLayer;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ConfigurationMerger::class)]
final class ConfigurationMergerReflectionTest extends TestCase
{
    use ConfigurationArrayAssertTrait;

    /**
     * @param array<string, mixed>|string $value
     */
    private function resolveListEntryKey(string $section, mixed $value, int|string $index): string
    {
        $method = (new ReflectionClass(ConfigurationMerger::class))->getMethod('resolveListEntryKey');
        $result = $method->invoke(new ConfigurationMerger(), $section, $value, $index);
        self::assertIsString($result);

        return $result;
    }

    /**
     * @return array{0: mixed, 1: int|null}
     */
    private function unwrapEntry(mixed $entry): array
    {
        $method = (new ReflectionClass(ConfigurationMerger::class))->getMethod('unwrapEntry');
        $result = $method->invoke(new ConfigurationMerger(), $entry);
        self::assertIsArray($result);
        self::assertCount(2, $result);

        /** @var array{0: mixed, 1: int|null} $typed */
        $typed = $result;

        return $typed;
    }

    private function isListSection(string $section): bool
    {
        $method = (new ReflectionClass(ConfigurationMerger::class))->getMethod('isListSection');
        $result = $method->invoke(new ConfigurationMerger(), $section);
        self::assertIsBool($result);

        return $result;
    }

    public function testResolveListEntryKeyForScanDirectory(): void
    {
        self::assertSame(
            'scan:/app/src',
            $this->resolveListEntryKey('scan', ['directory' => '/app/src'], 0),
        );
    }

    public function testResolveListEntryKeyForStringValue(): void
    {
        self::assertSame(
            'autowire:' . Clock::class,
            $this->resolveListEntryKey('autowire', Clock::class, 0),
        );
    }

    public function testResolveListEntryKeyForNonStringValueUsesIndex(): void
    {
        self::assertSame(
            'register_attributes:1',
            $this->resolveListEntryKey('register_attributes', ['class' => Clock::class], 1),
        );
    }

    public function testResolveListEntryKeyDoesNotTreatAutowireArrayAsScan(): void
    {
        self::assertSame(
            'autowire:0',
            $this->resolveListEntryKey('autowire', ['directory' => '/tmp'], 0),
        );
    }

    public function testUnwrapEntryReturnsValueWrapperParts(): void
    {
        [$value, $priority] = $this->unwrapEntry(['value' => 'x', 'priority' => 7]);

        self::assertSame('x', $value);
        self::assertSame(7, $priority);
    }

    public function testUnwrapEntryStripsInlinePriorityFromArray(): void
    {
        [$value, $priority] = $this->unwrapEntry(['priority' => 4, 'class' => FileLogger::class]);

        self::assertSame(['class' => FileLogger::class], $value);
        self::assertSame(4, $priority);
    }

    public function testUnwrapEntryReturnsNullPriorityForScalar(): void
    {
        [$value, $priority] = $this->unwrapEntry('plain');

        self::assertSame('plain', $value);
        self::assertNull($priority);
    }

    public function testIsListSectionRecognizesConfiguredSections(): void
    {
        self::assertTrue($this->isListSection('autowire'));
        self::assertTrue($this->isListSection('register_attributes'));
        self::assertTrue($this->isListSection('scan'));
        self::assertFalse($this->isListSection('services'));
    }

    public function testMergeUsesListSemanticsForAutowireSection(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['autowire' => [Clock::class, FileLogger::class]], 0, null),
        ]);

        self::assertSame([Clock::class, FileLogger::class], $merged['autowire']);
    }

    public function testMergeUsesMapSemanticsForServicesSection(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => ['a' => 1, 'b' => 2]], 0, null),
        ]);

        self::assertSame(['a' => 1, 'b' => 2], $merged['services']);
    }

    public function testConsiderWinnerDoesNotReplaceWhenCandidateHasLowerPriority(): void
    {
        $merged = (new ConfigurationMerger())->merge([
            new ConfigurationLayer(['services' => ['id' => 'keep']], 0, 100),
            new ConfigurationLayer(['services' => ['id' => 'drop']], 1, 10),
        ]);

        $services = $this->assertConfigMap($merged, 'services');

        self::assertSame('keep', $services['id']);
    }

    public function testBuildContextualResultSkipsInvalidWinnerEntries(): void
    {
        $method = (new ReflectionClass(ConfigurationMerger::class))->getMethod('buildContextualResult');
        /** @var array<string, array<string, string>> $result */
        $result = $method->invoke(new ConfigurationMerger(), [
            0 => ['value' => 'ignored-service'],
            'MissingSeparator' => ['value' => 'ignored-service'],
            'Consumer::need' => ['value' => 42],
            'ValidConsumer::validNeed' => ['value' => 'target.service'],
        ]);

        self::assertSame(
            ['ValidConsumer' => ['validNeed' => 'target.service']],
            $result,
        );
    }
}
