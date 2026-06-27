<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Autowire;

/**
 * Сервис с явным id через {@see Autowire}.
 */
final class AutowireAttributeService
{
    public function __construct(
        #[Autowire(service: 'app.clock')]
        public Clock $clock,
    ) {
    }
}
