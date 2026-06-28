<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\MemoryPool;

use CloudCastle\DI\Contract\PoolableInterface;

/**
 * Счётчик созданий и сбросов для тестов object pool (#63).
 */
final class ResetCounter implements PoolableInterface
{
    private static int $createdCount = 0;

    private static int $resetCount = 0;

    public function __construct(
        public int $value = 0,
    ) {
        ++self::$createdCount;
    }

    public function reset(): void
    {
        ++self::$resetCount;
        $this->value = 0;
    }

    public static function createdCount(): int
    {
        return self::$createdCount;
    }

    public static function resetCount(): int
    {
        return self::$resetCount;
    }

    public static function resetCounters(): void
    {
        self::$createdCount = 0;
        self::$resetCount = 0;
    }
}
