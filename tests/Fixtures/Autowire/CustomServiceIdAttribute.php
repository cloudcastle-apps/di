<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Attribute;
use CloudCastle\DI\Contract\ServiceIdAttribute;

/**
 * Пользовательский attribute для тестов {@see \CloudCastle\DI\Container::registerAttribute()}.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final readonly class CustomServiceIdAttribute implements ServiceIdAttribute
{
    /**
     * @param string|null $service Id сервиса в контейнере
     */
    public function __construct(
        public ?string $service = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function serviceId(): ?string
    {
        return $this->service;
    }
}
