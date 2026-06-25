<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Holder с nullable union property.
 */
final class NullableUnionPropertyHolder
{
    private Clock|LoggerInterface|null $dependency;

    public function getDependency(): Clock|LoggerInterface|null
    {
        return $this->dependency;
    }
}
