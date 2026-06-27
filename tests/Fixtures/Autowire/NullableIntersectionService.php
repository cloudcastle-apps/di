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
    public (Iterator&Countable)|null $storage;

    public function __construct(
        (Iterator&Countable)|null $storage = null,
    ) {
        $this->storage = $storage;
    }
}
