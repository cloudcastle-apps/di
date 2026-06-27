<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Inject;

/**
 * Сервис с явным id через {@see Inject}.
 */
final class InjectAttributeService
{
    public function __construct(
        #[Inject('app.clock')]
        public Clock $clock,
    ) {
    }
}
