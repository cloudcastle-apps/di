<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Attribute;

/**
 * Сервис с посторонним attribute на параметре.
 */
final readonly class DeprecatedAttributeService
{
    public function __construct(
        #[Deprecated]
        public Clock $clock,
    ) {
    }
}
