<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Opt-in TTL для singleton-кэша {@see Container::get()} (#64).
 *
 * По умолчанию TTL не задан — поведение как раньше (кэш до {@see forget()} / {@see set()}).
 */
final class ContainerSmartCacheSupport
{
    private readonly ServiceTtlRegistry $registry;

    /** @var array<string, float> Время кэширования (unix timestamp) */
    private array $cachedAt = [];

    /** @var callable(): float */
    private $clock;

    /**
     * @param callable(): float|null $clock Источник времени для тестов
     */
    public function __construct(?callable $clock = null)
    {
        $this->registry = new ServiceTtlRegistry();
        $this->clock = $clock ?? static fn (): float => microtime(true);
    }

    /**
     * Задаёт TTL для id сервиса.
     */
    public function configureFor(string $serviceId, int $ttlSeconds): void
    {
        $this->registry->setServiceTtl($serviceId, $ttlSeconds);
    }

    /**
     * Задаёт TTL для всех сервисов с тегом.
     */
    public function configureTagFor(string $tag, int $ttlSeconds): void
    {
        $this->registry->setTagTtl($tag, $ttlSeconds);
    }

    /**
     * Удаляет просроченную запись из singleton-кэша, если TTL настроен.
     *
     * @param list<string> $serviceTags
     * @param array<string, mixed> $resolved
     */
    public function evictIfExpired(string $serviceId, array $serviceTags, array &$resolved): void
    {
        if (!isset($resolved[$serviceId])) {
            return;
        }

        $ttl = $this->registry->effectiveTtl($serviceId, $serviceTags);

        if ($ttl === null) {
            return;
        }

        if (!$this->isExpired($serviceId, $ttl)) {
            return;
        }

        unset($resolved[$serviceId], $this->cachedAt[$serviceId]);
    }

    /**
     * Фиксирует момент кэширования singleton-экземпляра.
     */
    public function touch(string $serviceId): void
    {
        $this->cachedAt[$serviceId] = ($this->clock)();
    }

    /**
     * Явно удаляет singleton-кэш id и метку времени.
     *
     * @param array<string, mixed> $resolved
     */
    public function forget(string $serviceId, array &$resolved): void
    {
        unset($resolved[$serviceId], $this->cachedAt[$serviceId]);
    }

    /**
     * Удаляет singleton-кэш для каждого id из списка тега.
     *
     * @param list<string> $serviceIds
     * @param array<string, mixed> $resolved
     */
    public function forgetMany(array $serviceIds, array &$resolved): void
    {
        foreach ($serviceIds as $serviceId) {
            $this->forget($serviceId, $resolved);
        }
    }

    /**
     * Очищает весь singleton-кэш и метки времени.
     *
     * @param array<string, mixed> $resolved
     */
    public function forgetAll(array &$resolved): void
    {
        $resolved = [];
        $this->cachedAt = [];
    }

    /**
     * @param list<string> $serviceTags
     * @param array<string, mixed> $resolved
     *
     * @return array{
     *     configured: bool,
     *     ttl_seconds: int|null,
     *     cached: bool,
     *     expires_at: float|null,
     *     expired: bool
     * }
     */
    public function stats(string $serviceId, array $serviceTags, array $resolved): array
    {
        $ttl = $this->registry->effectiveTtl($serviceId, $serviceTags);
        $cached = isset($resolved[$serviceId]);
        $cachedAt = $this->cachedAt[$serviceId] ?? null;
        $expiresAt = ($ttl !== null && $cachedAt !== null) ? $cachedAt + (float) $ttl : null;
        $expired = $cached
            && $ttl !== null
            && $this->isExpired($serviceId, $ttl);

        return [
            'configured' => $ttl !== null,
            'ttl_seconds' => $ttl,
            'cached' => $cached,
            'expires_at' => $expiresAt,
            'expired' => $expired,
        ];
    }

    /**
     * Проверяет, истёк ли TTL singleton-кэша для id.
     *
     * @param string $serviceId Id сервиса
     * @param int $ttl Длительность кэша в секундах
     *
     * @return bool `true`, если метка времени отсутствует или TTL истёк
     */
    private function isExpired(string $serviceId, int $ttl): bool
    {
        if (!isset($this->cachedAt[$serviceId])) {
            return true;
        }

        return ($this->clock)() - $this->cachedAt[$serviceId] >= $ttl;
    }
}
