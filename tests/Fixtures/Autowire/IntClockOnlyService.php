<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Union int|Clock без значения по умолчанию.
 */
final readonly class IntClockOnlyService
{
    public function __construct(
        public int|Clock $dependency,
    ) {
    }
}
