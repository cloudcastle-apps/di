<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Holder с union typed property.
 */
final class UnionPropertyHolder
{
    private Clock|LoggerInterface $dependency;

    public function getDependency(): Clock|LoggerInterface
    {
        return $this->dependency;
    }
}
