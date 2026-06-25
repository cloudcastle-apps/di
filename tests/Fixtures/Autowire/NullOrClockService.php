<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union null|Clock без регистрации Clock.
 */
final readonly class NullOrClockService
{
    public function __construct(
        public null|Clock $clock,
    ) {
    }
}
