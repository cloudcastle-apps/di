<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Публичный API smart cache с TTL (#64).
 *
 * @see ContainerSmartCacheSupport
 */
trait ContainerSmartCacheApi
{
    public function cacheFor(string $serviceId, int $ttlSeconds): void
    {
        $this->smartCache->configureFor($serviceId, $ttlSeconds);
    }

    public function cacheTagFor(string $tag, int $ttlSeconds): void
    {
        $this->smartCache->configureTagFor($tag, $ttlSeconds);
    }

    public function forget(string $serviceId): void
    {
        $resolvedId = $this->aliasResolver->resolve($serviceId);
        $this->smartCache->forget($resolvedId, $this->resolved);
    }

    public function forgetTag(string $tag): void
    {
        $serviceIds = [];

        foreach ($this->tags[$tag] ?? [] as $serviceId) {
            $serviceIds[] = $this->aliasResolver->resolve($serviceId);
        }

        $this->smartCache->forgetMany($serviceIds, $this->resolved);
    }

    public function forgetAll(): void
    {
        $this->smartCache->forgetAll($this->resolved);
    }

    /**
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
     * @return list<string>
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
