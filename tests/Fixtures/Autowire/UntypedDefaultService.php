<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с нетипизированным параметром и значением по умолчанию.
 */
final class UntypedDefaultService
{
    public function __construct(
        public mixed $value = 'default',
    ) {
    }
}
