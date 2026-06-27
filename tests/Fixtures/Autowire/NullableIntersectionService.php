<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Countable;
use Iterator;

/**
 * Сервис с nullable intersection-типом.
 */
final class NullableIntersectionService
{
    public function __construct(
        public (Iterator&Countable)|null $storage = null,
    ) {
    }
}
