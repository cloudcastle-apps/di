<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use RuntimeException;

/**
 * Сервис: static inject-метод бросает исключение при вызове.
 */
final class StaticThrowMethodService
{
    private Clock $clock;

    public static function injectStatic(Clock $clock): void
    {
        throw new RuntimeException('static inject must not run: ' . $clock::class);
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
