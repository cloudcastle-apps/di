<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с promoted property — повторное property injection пропускается.
 */
final class PromotedPropertyService
{
    public function __construct(
        private Clock $clock,
    ) {
    }

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
