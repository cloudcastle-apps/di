<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationLayer;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationMerger::class)]
final class ConfigurationMergerEdgeCasesTest extends TestCase
{
    /**
     * @param list<ConfigurationLayer> $layers
     * @param array<string, mixed> $expected
     */
    #[DataProvider('edgeCaseProvider')]
    public function testMergeEdgeCases(array $layers, array $expected): void
    {
        $merged = (new ConfigurationMerger())->merge($layers);

        foreach (array_keys($expected) as $section) {
            self::assertArrayHasKey($section, $merged);
            self::assertSame($expected[$section], $merged[$section]);
        }
    }

    /**
     * @return iterable<string, array{list<ConfigurationLayer>, array<string, mixed>}>
     */
    public static function edgeCaseProvider(): iterable
    {
        yield 'scan-two-directories' => [
            [
                new ConfigurationLayer(['scan' => [['directory' => '/one']]], 0, null),
                new ConfigurationLayer(['scan' => [['directory' => '/two']]], 1, null),
            ],
            ['scan' => [['directory' => '/one'], ['directory' => '/two']]],
        ];

        yield 'scan-entry-without-directory' => [
            [
                new ConfigurationLayer(['scan' => [['namespace' => 'App']]], 0, null),
            ],
            ['scan' => [['namespace' => 'App']]],
        ];

        yield 'services-explicit-zero-priority' => [
            [
                new ConfigurationLayer(
                    ['services' => ['k' => ['value' => 'zero', 'priority' => 0]]],
                    0,
                    10,
                ),
                new ConfigurationLayer(['services' => ['k' => 'layer']], 1, 5),
            ],
            ['services' => ['k' => 'layer']],
        ];
    }
}
