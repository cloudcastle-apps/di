<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Публичный API opt-in object pool (#63).
 *
 * Делегирует в {@see ContainerMemoryPoolSupport}. Подключается к {@see Container} через use-trait.
 *
 * @see ContainerInterface
 * @see ContainerMemoryPoolSupport
 */
trait ContainerMemoryPoolApi
{
    /**
     * {@inheritDoc}
     */
    public function enablePooling(string $serviceId, int $maxSize = ServiceObjectPool::DEFAULT_MAX_SIZE): void
    {
        $this->memoryPool->enable($serviceId, $maxSize);
    }

    /**
     * {@inheritDoc}
     */
    public function disablePooling(string $serviceId): void
    {
        $this->memoryPool->disable($serviceId);
    }

    /**
     * {@inheritDoc}
     */
    public function isPoolingEnabled(string $serviceId): bool
    {
        return $this->memoryPool->isEnabled($serviceId);
    }

    /**
     * {@inheritDoc}
     */
    public function releaseToPool(string $serviceId, object $instance): void
    {
        $this->memoryPool->release($serviceId, $instance);
    }

    /**
     * {@inheritDoc}
     */
    public function clearPool(string $serviceId): void
    {
        $this->memoryPool->clear($serviceId);
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllPools(): void
    {
        $this->memoryPool->clearAll();
    }

    /**
     * {@inheritDoc}
     *
     * @return array{configured: bool, max_size: int, available: int}
     */
    public function poolStats(string $serviceId): array
    {
        return $this->memoryPool->stats($serviceId);
    }
}
