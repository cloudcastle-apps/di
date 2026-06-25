<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Holder без типа свойства для тестов resolver.
 */
final class UntypedPropertyHolder
{
    private $value;

    public function getValue(): mixed
    {
        return $this->value;
    }
}
