<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с одной типизированной зависимостью.
 */
final class LoggerUser
{
    public function __construct(
        public Clock $clock,
    ) {
    }
}
