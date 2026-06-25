<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Holder со scalar typed property.
 */
final class BuiltinPropertyHolder
{
    private int $count;

    public function getCount(): int
    {
        return $this->count;
    }
}
