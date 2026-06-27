<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Вторая часть циклической зависимости.
 */
final class CircularB
{
    public function __construct(
        public CircularA $partner,
    ) {
    }
}
