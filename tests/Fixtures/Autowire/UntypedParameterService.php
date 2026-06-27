<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с нетипизированным обязательным параметром.
 */
final class UntypedParameterService
{
    public function __construct(
        public mixed $value,
    ) {
    }
}
