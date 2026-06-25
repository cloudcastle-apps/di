<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис со static inject-методом — пропускается MethodInjector.
 */
final class StaticMethodInjectService
{
    private Clock $clock;

    public static function injectClock(): void
    {
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
