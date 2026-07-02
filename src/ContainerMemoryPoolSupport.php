<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Opt-in object pool для {@see Container::make()} (#63).
 *
 * По умолчанию выключен — {@see make()} создаёт новый экземпляр, как раньше.
 * При включённом pooling {@see make()} переиспользует экземпляры из пула;
 * {@see release()} возвращает объект после использования.
 *
 * @see ServiceObjectPool
 * @see ContainerMemoryPoolApi
 */
final class ContainerMemoryPoolSupport
{
    /**
     * Реестр пулов по id сервисов.
     */
    private readonly ServiceObjectPool $pool;

    /**
     * Создаёт поддержку pooling с пустым реестром.
     */
    public function __construct()
    {
        $this->pool = new ServiceObjectPool();
    }

    /**
     * Включает pooling для id с ограничением размера свободного пула.
     *
     * @param string $serviceId Id сервиса (как в {@see Container::make()})
     * @param int $maxSize Максимум свободных экземпляров в пуле
     */
    public function enable(string $serviceId, int $maxSize = ServiceObjectPool::DEFAULT_MAX_SIZE): void
    {
        $this->pool->configure($serviceId, $maxSize);
    }

    /**
     * Отключает pooling для id и удаляет накопленные свободные экземпляры.
     *
     * @param string $serviceId Id сервиса
     */
    public function disable(string $serviceId): void
    {
        $this->pool->remove($serviceId);
    }

    /**
     * Проверяет, настроен ли pooling для id.
     *
     * @param string $serviceId Id сервиса
     *
     * @return bool `true`, если pooling включён
     */
    public function isEnabled(string $serviceId): bool
    {
        return $this->pool->isConfigured($serviceId);
    }

    /**
     * Возвращает экземпляр из пула или создаёт новый через `$resolver`.
     *
     * @param string $serviceId Id сервиса
     * @param callable(): mixed $resolver Фабрика нового экземпляра, если пул пуст
     *
     * @return mixed Экземпляр из пула или только что созданный
     */
    public function make(string $serviceId, callable $resolver): mixed
    {
        return $this->pool->acquire($serviceId, $resolver);
    }

    /**
     * Возвращает экземпляр в пул после использования.
     *
     * @param string $serviceId Id сервиса
     * @param object $instance Экземпляр, созданный через {@see make()}
     *
     * @throws Exception\ContainerException Если pooling для id не включён
     */
    public function release(string $serviceId, object $instance): void
    {
        $this->pool->release($serviceId, $instance);
    }

    /**
     * Удаляет свободные экземпляры в пуле id без отключения pooling.
     *
     * @param string $serviceId Id сервиса
     */
    public function clear(string $serviceId): void
    {
        $this->pool->clear($serviceId);
    }

    /**
     * Удаляет свободные экземпляры во всех включённых пулах.
     */
    public function clearAll(): void
    {
        $this->pool->clearAll();
    }

    /**
     * Возвращает статистику пула для id.
     *
     * @param string $serviceId Id сервиса
     *
     * @return array{configured: bool, max_size: int, available: int}
     */
    public function stats(string $serviceId): array
    {
        return $this->pool->stats($serviceId);
    }
}
