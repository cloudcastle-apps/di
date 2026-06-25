<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Inject;

/**
 * Сервис с inject-методом через attribute на параметре.
 */
final class MethodParameterInjectService
{
    private Clock $clock;

    protected function assignClock(#[Inject] Clock $clock): void
    {
        $this->clock = $clock;
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
