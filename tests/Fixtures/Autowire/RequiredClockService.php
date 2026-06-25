<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с обязательной зависимостью Clock.
 */
final readonly class RequiredClockService
{
    public function __construct(
        public Clock $clock,
    ) {
    }
}
