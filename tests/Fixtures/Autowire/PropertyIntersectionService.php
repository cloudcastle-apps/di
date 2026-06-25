<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Countable;
use Iterator;

/**
 * Сервис со свойством intersection-типа.
 */
final class PropertyIntersectionService
{
    private Iterator&Countable $storage;

    public function getStorage(): Iterator&Countable
    {
        return $this->storage;
    }
}
