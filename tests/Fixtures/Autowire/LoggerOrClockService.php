<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Union из двух object-типов: первый недоступен, второй — Clock.
 */
final readonly class LoggerOrClockService
{
    public function __construct(
        public LoggerInterface|Clock $dependency,
    ) {
    }
}
