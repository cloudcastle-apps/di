<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Iterator;

/**
 * Сервис с nullable опциональной зависимостью (PHP 8.1+).
 *
 * Nullable intersection (Iterator&Countable)|null — только PHP 8.2+; покрытие в {@see IntersectionTypeResolverTest}.
 */
final class NullableIntersectionService
{
    public function __construct(
        public ?Iterator $storage = null,
    ) {
    }
}
