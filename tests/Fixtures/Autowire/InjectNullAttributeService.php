<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Inject;

/**
 * Сервис с {@see Inject} без явного id — fallback на autowiring по типу.
 */
final class InjectNullAttributeService
{
    public function __construct(
        #[Inject]
        public Clock $clock,
    ) {
    }
}
