<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union string|null и значением по умолчанию.
 */
final readonly class StringOrNullService
{
    public function __construct(
        public string|null $label = 'default',
    ) {
    }
}
