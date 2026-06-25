<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с nullable typed property.
 */
final class NullablePropertyService
{
    private ?Clock $clock;

    public function getClock(): ?Clock
    {
        return $this->clock;
    }
}
