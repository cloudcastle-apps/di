<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Autowire;

/**
 * Сервис с {@see Autowire} без явного id — fallback на autowiring по типу.
 */
final class AutowireNullAttributeService
{
    public function __construct(
        #[Autowire]
        public Clock $clock,
    ) {
    }
}
