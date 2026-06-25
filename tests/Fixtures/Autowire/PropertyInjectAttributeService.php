<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Attribute\Inject;

/**
 * Сервис с внедрением через свойство.
 */
final class PropertyInjectAttributeService
{
    #[Inject('app.clock')]
    private Clock $clock;

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
