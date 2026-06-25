<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Autowire;

/**
 * Сервис с property через #[Autowire] с явным id.
 */
final class PropertyAutowireAttributeService
{
    #[Autowire(service: 'app.clock')]
    private Clock $clock;

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
