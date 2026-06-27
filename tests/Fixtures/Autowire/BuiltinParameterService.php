<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис со встроенным типом и значением по умолчанию.
 */
final class BuiltinParameterService
{
    public function __construct(
        public string $label = 'default',
    ) {
    }
}
