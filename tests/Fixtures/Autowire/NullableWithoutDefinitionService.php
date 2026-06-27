<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Сервис с nullable-типом без явной регистрации зависимости.
 */
final class NullableWithoutDefinitionService
{
    public function __construct(
        public ?Clock $clock,
    ) {
    }
}
