<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Первая часть циклической зависимости.
 */
final readonly class CircularA
{
    public function __construct(
        public CircularB $partner,
    ) {
    }
}
