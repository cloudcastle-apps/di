<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Holder с typed property.
 */
final class TypedPropertyHolder
{
    private Clock $clock;

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
