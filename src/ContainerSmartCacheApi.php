<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Публичный API smart cache с TTL (#64).
 *
 * Делегирует в {@see ContainerSmartCacheSupport}. Подключается к {@see Container} через use-trait.
 *
 * @see ContainerInterface::cacheFor()
 * @see ContainerSmartCacheSupport
 */
trait ContainerSmartCacheApi
{
    /**
     * {@inheritDoc}
     */
    public function cacheFor(string $serviceId, int $ttlSeconds): void
    {
        $this->smartCache->configureFor($serviceId, $ttlSeconds);
    }

    /**
     * {@inheritDoc}
     */
    public function cacheTagFor(string $tag, int $ttlSeconds): void
    {
        $this->smartCache->configureTagFor($tag, $ttlSeconds);
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $serviceId): void
    {
        $resolvedId = $this->aliasResolver->resolve($serviceId);
        $this->smartCache->forget($resolvedId, $this->resolved);
    }

    /**
     * {@inheritDoc}
     */
    public function forgetTag(string $tag): void
    {
        $serviceIds = [];

        foreach ($this->tags[$tag] ?? [] as $serviceId) {
            $serviceIds[] = $this->aliasResolver->resolve($serviceId);
        }

        $this->smartCache->forgetMany($serviceIds, $this->resolved);
    }

    /**
     * {@inheritDoc}
     */
    public function forgetAll(): void
    {
        $this->smartCache->forgetAll($this->resolved);
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *     configured: bool,
     *     ttl_seconds: int|null,
     *     cached: bool,
     *     expires_at: float|null,
     *     expired: bool
     * }
     */
    public function cacheStats(string $serviceId): array
    {
        $resolvedId = $this->aliasResolver->resolve($serviceId);

        return $this->smartCache->stats(
            $resolvedId,
            $this->tagsForService($resolvedId),
            $this->resolved,
        );
    }

    /**
     * Возвращает имена тегов, в которых зарегистрирован сервис.
     *
     * @param string $serviceId Id сервиса после разрешения alias
     *
     * @return list<string> Список имён тегов
     */
    private function tagsForService(string $serviceId): array
    {
        $serviceTags = [];

        foreach ($this->tags as $tag => $taggedIds) {
            if (\in_array($serviceId, $taggedIds, true)) {
                $serviceTags[] = $tag;
            }
        }

        return $serviceTags;
    }
}
