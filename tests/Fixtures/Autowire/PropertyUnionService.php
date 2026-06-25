<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с union-typed property.
 */
final class PropertyUnionService
{
    private LoggerInterface|Clock $dependency;

    public function getDependency(): LoggerInterface|Clock
    {
        return $this->dependency;
    }
}
