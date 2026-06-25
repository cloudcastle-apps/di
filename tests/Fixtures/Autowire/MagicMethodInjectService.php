<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с magic-методом — пропускается MethodInjector.
 */
final class MagicMethodInjectService
{
    private Clock $clock;

    public function __call(string $name, array $arguments): void
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
