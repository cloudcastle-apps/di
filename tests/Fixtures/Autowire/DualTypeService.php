<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union-типом string|Clock.
 */
final class DualTypeService
{
    public function __construct(
        public Clock|string $value,
    ) {
    }
}
