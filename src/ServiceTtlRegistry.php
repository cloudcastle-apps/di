<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Exception\ContainerException;

/**
 * Хранилище TTL per id и per tag для smart cache (#64).
 */
final class ServiceTtlRegistry
{
    /** @var array<string, int> TTL в секундах для id сервиса */
    private array $serviceTtl = [];

    /** @var array<string, int> TTL в секундах для тега */
    private array $tagTtl = [];

    /**
     * Задаёт TTL для singleton-кэша id.
     *
     * @param string $serviceId Id сервиса
     * @param int $ttlSeconds Длительность кэша в секундах (не меньше 1)
     *
     * @throws ContainerException Если `$ttlSeconds` меньше 1
     */
    public function setServiceTtl(string $serviceId, int $ttlSeconds): void
    {
        if ($ttlSeconds < 1) {
            throw new ContainerException(\sprintf(
                'TTL для сервиса "%s" должен быть не меньше 1 секунды, получено %d.',
                $serviceId,
                $ttlSeconds,
            ));
        }

        $this->serviceTtl[$serviceId] = $ttlSeconds;
    }

    /**
     * Задаёт TTL для всех сервисов с указанным тегом.
     *
     * @param string $tag Имя тега
     * @param int $ttlSeconds Длительность кэша в секундах (не меньше 1)
     *
     * @throws ContainerException Если `$ttlSeconds` меньше 1
     */
    public function setTagTtl(string $tag, int $ttlSeconds): void
    {
        if ($ttlSeconds < 1) {
            throw new ContainerException(\sprintf(
                'TTL для тега "%s" должен быть не меньше 1 секунды, получено %d.',
                $tag,
                $ttlSeconds,
            ));
        }

        $this->tagTtl[$tag] = $ttlSeconds;
    }

    /**
     * Возвращает эффективный TTL: явный для id или минимальный среди тегов сервиса.
     *
     * @param string $serviceId Id сервиса
     * @param list<string> $serviceTags Имена тегов, в которых зарегистрирован сервис
     *
     * @return int|null TTL в секундах или `null`, если TTL не настроен
     */
    public function effectiveTtl(string $serviceId, array $serviceTags): ?int
    {
        if (isset($this->serviceTtl[$serviceId])) {
            return $this->serviceTtl[$serviceId];
        }

        $tagTtls = [];

        foreach ($serviceTags as $tag) {
            if (isset($this->tagTtl[$tag])) {
                $tagTtls[] = $this->tagTtl[$tag];
            }
        }

        if ($tagTtls === []) {
            return null;
        }

        return min($tagTtls);
    }
}
