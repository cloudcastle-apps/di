<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с setter без attributes — требует enableMethodAutowiring().
 */
final class SetterInjectService
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
