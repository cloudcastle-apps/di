<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Считает вызовы конструктора.
 */
final class ConstructCountService
{
    public static int $constructCount = 0;

    private Clock $clock;

    public function __construct(Clock $clock)
    {
        ++self::$constructCount;
        $this->clock = $clock;
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
