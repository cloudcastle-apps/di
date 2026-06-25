<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Inject;

/**
 * Сервис с inject-методом.
 */
final class MethodInjectService
{
    private Clock $clock;

    #[Inject]
    protected function setClock(Clock $clock): void
    {
        $this->clock = $clock;
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
