<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

/**
 * Вспомогательные проверки типов для массивов конфигурации в unit-тестах.
 */
trait ConfigurationArrayAssertTrait
{
    /**
     * @return array<string, mixed>
     */
    private function assertConfigArray(mixed $config): array
    {
        self::assertIsArray($config);

        /** @var array<string, mixed> $typed */
        $typed = $config;

        return $typed;
    }

    /**
     * @return array<string, mixed>
     */
    private function assertConfigMap(mixed $config, string $section): array
    {
        $root = $this->assertConfigArray($config);
        self::assertIsArray($root[$section] ?? null);

        /** @var array<string, mixed> $sectionData */
        $sectionData = $root[$section];

        return $sectionData;
    }

    /**
     * @return list<mixed>
     */
    private function assertConfigList(mixed $config, string $section): array
    {
        $root = $this->assertConfigArray($config);
        self::assertIsArray($root[$section] ?? null);

        /** @var list<mixed> $sectionData */
        $sectionData = $root[$section];

        return $sectionData;
    }
}
