<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с typed property без конструктора.
 */
final class TypedPropertyService
{
    private Clock $clock;

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
