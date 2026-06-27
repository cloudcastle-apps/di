<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union int|Clock — встроенный тип пропускается, Clock создаётся через autowiring.
 */
final class IntClockService
{
    public function __construct(
        public int|Clock $value,
    ) {
    }
}
