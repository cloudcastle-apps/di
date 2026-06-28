<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Публичный API opt-in object pool (#63).
 *
 * @see ContainerMemoryPoolSupport
 */
trait ContainerMemoryPoolApi
{
    public function enablePooling(string $serviceId, int $maxSize = ServiceObjectPool::DEFAULT_MAX_SIZE): void
    {
        $this->memoryPool->enable($serviceId, $maxSize);
    }

    public function disablePooling(string $serviceId): void
    {
        $this->memoryPool->disable($serviceId);
    }

    public function isPoolingEnabled(string $serviceId): bool
    {
        return $this->memoryPool->isEnabled($serviceId);
    }

    public function releaseToPool(string $serviceId, object $instance): void
    {
        $this->memoryPool->release($serviceId, $instance);
    }

    public function clearPool(string $serviceId): void
    {
        $this->memoryPool->clear($serviceId);
    }

    public function clearAllPools(): void
    {
        $this->memoryPool->clearAll();
    }

    /**
     * @return array{configured: bool, max_size: int, available: int}
     */
    public function poolStats(string $serviceId): array
    {
        return $this->memoryPool->stats($serviceId);
    }
}
