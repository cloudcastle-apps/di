<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с пользовательским attribute без явного id (fallback по типу).
 */
final class CustomAttributeTypedPropertyService
{
    #[CustomServiceIdAttribute]
    private Clock $clock;

    public function getClock(): Clock
    {
        return $this->clock;
    }
}
