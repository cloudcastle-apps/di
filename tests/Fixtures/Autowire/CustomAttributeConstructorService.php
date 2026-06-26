<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с пользовательским attribute в конструкторе.
 */
final class CustomAttributeConstructorService
{
    public function __construct(
        #[CustomServiceIdAttribute(service: 'app.clock')]
        private Clock $clock,
    ) {
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
