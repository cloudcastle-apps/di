<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с зависимостью, разрешаемой по имени параметра `logger`.
 */
final readonly class NamedLoggerConsumer
{
    public function __construct(
        public LoggerInterface $logger,
    ) {
    }
}
