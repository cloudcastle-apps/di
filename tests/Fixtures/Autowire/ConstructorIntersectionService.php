<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Countable;
use Iterator;

/**
 * Сервис с обязательным intersection-типом в конструкторе.
 */
final class ConstructorIntersectionService
{
    public function __construct(
        private Iterator&Countable $storage,
    ) {
    }

    public function getStorage(): Iterator&Countable
    {
        return $this->storage;
    }
}
