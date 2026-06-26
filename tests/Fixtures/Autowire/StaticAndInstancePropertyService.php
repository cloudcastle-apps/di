<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис со static и instance typed properties.
 */
final class StaticAndInstancePropertyService
{
    private static ?Clock $staticClock = null;

    private Clock $instanceClock;

    public function getInstanceClock(): Clock
    {
        return $this->instanceClock;
    }

    public static function getStaticClock(): ?Clock
    {
        return self::$staticClock;
    }
}
