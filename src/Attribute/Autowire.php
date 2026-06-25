<?php

declare(strict_types=1);

namespace CloudCastle\DI\Attribute;

use Attribute;

/**
 * Явно задаёт сервис контейнера для параметра конструктора, свойства или метода (аналог {@see Inject}).
 *
 * Синтаксис `#[Autowire(service: 'id')]` совместим с распространёнными DI-конvention.
 *
 * @psalm-suppress PossiblyUnusedMethod Конструктор вызывается через reflection attributes
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final readonly class Autowire
{
    /**
     * @param string|null $service Идентификатор сервиса; `null` — не переопределять стратегию
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        public ?string $service = null,
    ) {
    }
}
