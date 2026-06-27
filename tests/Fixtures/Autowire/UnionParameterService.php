<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union-типом в конструкторе.
 */
final class UnionParameterService
{
    public function __construct(
        public Clock|null $clock,
    ) {
    }
}
