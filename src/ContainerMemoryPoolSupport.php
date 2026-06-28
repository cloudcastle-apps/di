<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Opt-in object pool для {@see Container::make()} (#63).
 *
 * По умолчанию выключен — {@see make()} создаёт новый экземпляр, как раньше.
 */
final class ContainerMemoryPoolSupport
{
    private readonly ServiceObjectPool $pool;

    public function __construct()
    {
        $this->pool = new ServiceObjectPool();
    }

    public function enable(string $serviceId, int $maxSize = ServiceObjectPool::DEFAULT_MAX_SIZE): void
    {
        $this->pool->configure($serviceId, $maxSize);
    }

    public function disable(string $serviceId): void
    {
        $this->pool->remove($serviceId);
    }

    public function isEnabled(string $serviceId): bool
    {
        return $this->pool->isConfigured($serviceId);
    }

    public function make(string $serviceId, callable $resolver): mixed
    {
        return $this->pool->acquire($serviceId, $resolver);
    }

    public function release(string $serviceId, object $instance): void
    {
        $this->pool->release($serviceId, $instance);
    }

    public function clear(string $serviceId): void
    {
        $this->pool->clear($serviceId);
    }

    public function clearAll(): void
    {
        $this->pool->clearAll();
    }

    /**
     * @return array{configured: bool, max_size: int, available: int}
     */
    public function stats(string $serviceId): array
    {
        return $this->pool->stats($serviceId);
    }
}
