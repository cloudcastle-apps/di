<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с пользовательским attribute на свойстве.
 */
final class CustomAttributePropertyService
{
    #[CustomServiceIdAttribute(service: 'app.clock')]
    private Clock $clock;

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
