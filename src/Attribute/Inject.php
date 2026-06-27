<?php

declare(strict_types=1);

namespace CloudCastle\DI\Attribute;

use Attribute;
use CloudCastle\DI\Contract\ServiceIdAttribute;

/**
 * Указывает идентификатор сервиса для параметра конструктора, свойства или метода при autowiring.
 *
 * Если {@see $id} не задан, используются autowiring по имени параметра и по типу.
 *
 * @psalm-suppress PossiblyUnusedMethod Конструктор вызывается через reflection attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class Inject implements ServiceIdAttribute
{
    /**
     * @param string|null $id Идентификатор сервиса в контейнере; `null` — не переопределять стратегию
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        public ?string $id = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function serviceId(): ?string
    {
        return $this->id;
    }
}
