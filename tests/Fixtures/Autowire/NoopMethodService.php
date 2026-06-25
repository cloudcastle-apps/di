<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с методом без параметров — не должен вызываться MethodInjector.
 */
final class NoopMethodService
{
    private Clock $clock;

    public bool $noopCalled = false;

    public function noop(): void
    {
        $this->noopCalled = true;
    }

    public function setClock(Clock $clock): void
    {
        $this->clock = $clock;
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
