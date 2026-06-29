<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;

/**
 * Откладывает создание сервиса до первого вызова {@see getValue()}.
 *
 * Возвращается из {@see ContainerInterface::lazy()}. Удобно передавать в {@see ContainerInterface::set()}
 * как значение definition: тяжёлый сервис не создаётся при регистрации, только при обращении к обёртке.
 *
 * Внутренний кэш обёртки независим от singleton-кэша контейнера: повторные {@see getValue()}
 * возвращают один и тот же экземпляр, созданный при первом вызове.
 *
 * @see Container::lazy()
 */
final class LazyService
{
    /**
     * Кэшированное значение после первого {@see getValue()}.
     */
    private mixed $value = null;

    /**
     * Флаг: был ли уже вызван {@see ContainerInterface::get()} для {@see $serviceId}.
     */
    private bool $initialized = false;

    /**
     * @param ContainerInterface $container Контейнер для отложенного {@see ContainerInterface::get()}
     * @param string $serviceId Id сервиса, который будет разрешён при первом {@see getValue()}
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $serviceId,
    ) {
    }

    /**
     * Возвращает сервис, создавая его через {@see ContainerInterface::get()} при первом обращении.
     *
     * @return mixed Экземпляр сервиса или скаляр из контейнера
     */
    public function getValue(): mixed
    {
        if (!$this->initialized) {
            $this->value = $this->container->get($this->serviceId);
            $this->initialized = true;
        }

        return $this->value;
    }
}
