<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationLayer;
use CloudCastle\DI\Configuration\ConfigurationMerger;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationMerger::class)]
final class ConfigurationMergerDataTest extends TestCase
{
    /**
     * @param list<ConfigurationLayer> $layers
     * @param array<string, mixed> $expected
     */
    #[DataProvider('mergeScenarioProvider')]
    public function testMergeScenarios(array $layers, array $expected): void
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
    public static function mergeScenarioProvider(): iterable
    {
        yield 'services-last-wins' => [
            [
                new ConfigurationLayer(['services' => ['k' => 'first']], 0, null),
                new ConfigurationLayer(['services' => ['k' => 'second']], 1, null),
            ],
            ['services' => ['k' => 'second']],
        ];

        yield 'services-parameter-priority' => [
            [
                new ConfigurationLayer(
                    ['services' => ['k' => ['value' => 'high', 'priority' => 50]]],
                    0,
                    null,
                ),
                new ConfigurationLayer(['services' => ['k' => 'low']], 1, null),
            ],
            ['services' => ['k' => 'high']],
        ];

        yield 'services-file-priority' => [
            [
                new ConfigurationLayer(['services' => ['k' => 'early']], 0, 20),
                new ConfigurationLayer(['services' => ['k' => 'late']], 1, 5),
            ],
            ['services' => ['k' => 'early']],
        ];

        yield 'aliases-merge' => [
            [
                new ConfigurationLayer(['aliases' => ['a' => 'one']], 0, null),
                new ConfigurationLayer(['aliases' => ['b' => 'two']], 1, null),
            ],
            ['aliases' => ['a' => 'one', 'b' => 'two']],
        ];

        yield 'bind-merge' => [
            [
                new ConfigurationLayer(['bind' => ['A' => 'a']], 0, null),
                new ConfigurationLayer(['bind' => ['B' => 'b']], 1, null),
            ],
            ['bind' => ['A' => 'a', 'B' => 'b']],
        ];

        yield 'autowire-list-append' => [
            [
                new ConfigurationLayer(['autowire' => [Clock::class]], 0, null),
                new ConfigurationLayer(['autowire' => [FileLogger::class]], 1, null),
            ],
            ['autowire' => [Clock::class, FileLogger::class]],
        ];

        yield 'register-attributes-append' => [
            [
                new ConfigurationLayer(['register_attributes' => ['A']], 0, null),
                new ConfigurationLayer(['register_attributes' => ['B']], 1, null),
            ],
            ['register_attributes' => ['A', 'B']],
        ];

        yield 'tags-last-wins-for-same-tag' => [
            [
                new ConfigurationLayer(['tags' => ['t' => ['a']]], 0, null),
                new ConfigurationLayer(['tags' => ['t' => ['b']]], 1, null),
            ],
            ['tags' => ['t' => ['b']]],
        ];

        yield 'autowiring-flags-merge' => [
            [
                new ConfigurationLayer(['autowiring' => ['enabled' => true]], 0, null),
                new ConfigurationLayer(['autowiring' => ['method' => true]], 1, null),
            ],
            ['autowiring' => ['enabled' => true, 'method' => true]],
        ];

        yield 'scan-directory-priority' => [
            [
                new ConfigurationLayer(
                    ['scan' => [['directory' => '/src', 'namespace' => 'Old']]],
                    0,
                    1,
                ),
                new ConfigurationLayer(
                    ['scan' => [['directory' => '/src', 'namespace' => 'New']]],
                    1,
                    10,
                ),
            ],
            ['scan' => [['directory' => '/src', 'namespace' => 'New']]],
        ];
    }
}
