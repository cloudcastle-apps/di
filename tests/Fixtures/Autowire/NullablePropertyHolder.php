<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Holder с nullable typed property.
 */
final class NullablePropertyHolder
{
    private ?Clock $clock;

    public function getClock(): ?Clock
    {
        return $this->clock;
    }
}
