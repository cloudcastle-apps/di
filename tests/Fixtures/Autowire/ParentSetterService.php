<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Базовый класс с setter в родителе.
 */
class ParentSetterService
{
    private Clock $clock;

    public function setClock(Clock $clock): void
    {
        $this->clock = $clock;
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
