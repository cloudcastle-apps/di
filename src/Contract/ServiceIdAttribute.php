<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Контракт PHP attribute с явным идентификатором сервиса в контейнере.
 *
 * Пользовательские attributes регистрируются через {@see ContainerInterface::registerAttribute()}
 * и обрабатываются так же, как встроенные {@see \CloudCastle\DI\Attribute\Inject}
 * и {@see \CloudCastle\DI\Attribute\Autowire}.
 */
interface ServiceIdAttribute
{
    /**
     * Возвращает id сервиса из attribute или `null`, если attribute не переопределяет стратегию.
     *
     * @return string|null Id сервиса; `null` — не переопределять стратегию autowiring
     */
    public function serviceId(): ?string;
}
