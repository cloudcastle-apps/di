<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union string|Clock (string проверяется первым в reflection).
 */
final readonly class StringClockService
{
    public function __construct(
        public string|Clock $value,
    ) {
    }
}
