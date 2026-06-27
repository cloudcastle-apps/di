<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Contract\ServiceIdAttribute;

/**
 * Заглушка без #[\Attribute] для негативного теста регистрации.
 */
final class PlainServiceIdAttributeStub implements ServiceIdAttribute
{
    /**
     * {@inheritDoc}
     */
    public function serviceId(): ?string
    {
        return null;
    }
}
