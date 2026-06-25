<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с необязательной nullable-зависимостью.
 */
final readonly class OptionalDependency
{
    public function __construct(
        public ?Clock $clock = null,
    ) {
    }
}
