<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\LazyGhost;

final class HeavyService implements HeavyContract
{
    public static int $constructCount = 0;

    public function __construct()
    {
        ++self::$constructCount;
    }

    public function work(): string
    {
        return 'heavy-result';
    }
}
