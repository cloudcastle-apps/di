<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union встроенных типов и значением по умолчанию.
 */
final readonly class BuiltinUnionService
{
    public function __construct(
        public string|int $code = '200',
    ) {
    }
}
