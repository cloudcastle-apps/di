<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Свойство инициализируется в теле конструктора без promoted.
 */
final class ManualInitPropertyService
{
    private Clock $plain;

    public function __construct()
    {
        $this->plain = new Clock();
    }

    public function getPlain(): Clock
    {
        return $this->plain;
    }
}
