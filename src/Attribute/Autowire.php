<?php

declare(strict_types=1);

namespace CloudCastle\DI\Attribute;

use Attribute;
use CloudCastle\DI\Contract\ServiceIdAttribute;

/**
 * Явно задаёт сервис контейнера для параметра конструктора, свойства или метода (аналог {@see Inject}).
 *
 * Синтаксис `#[Autowire(service: 'id')]` совместим с распространёнными DI-конvention.
 *
 * @psalm-suppress PossiblyUnusedMethod Конструктор вызывается через reflection attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class Autowire implements ServiceIdAttribute
{
    /**
     * @param string|null $service Идентификатор сервиса; `null` — не переопределять стратегию
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        public readonly ?string $service = null,
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
