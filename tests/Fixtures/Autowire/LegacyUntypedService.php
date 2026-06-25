<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с нетипизированным параметром (legacy-синтаксис).
 */
final class LegacyUntypedService
{
    public mixed $value;

    public function __construct($value = 'legacy')
    {
        $this->value = $value;
    }
}
