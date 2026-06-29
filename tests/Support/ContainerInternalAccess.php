<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Support;

use CloudCastle\DI\ContainerMemoryPoolSupport;
use CloudCastle\DI\ContainerProfilingSupport;
use CloudCastle\DI\ContainerSmartCacheSupport;
use CloudCastle\DI\ServiceAliasResolver;
use CloudCastle\DI\ServiceObjectPool;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;

/**
 * Доступ к внутренним support-объектам контейнера в behavior-тестах без вызова public trait API.
 *
 * Публичные методы trait API покрываются только {@see Unit\ContainerMemoryPoolVisibilityTest},
 * {@see Unit\ContainerProfilingVisibilityTest} и {@see Unit\ContainerSmartCacheVisibilityTest},
 * чтобы мутации PublicVisibility давали KILLED, а не ERRORED.
 */
final class ContainerInternalAccess
{
    public static function enablePooling(
        object $container,
        string $serviceId,
        int $maxSize = ServiceObjectPool::DEFAULT_MAX_SIZE,
    ): void {
        self::memoryPool($container)->enable($serviceId, $maxSize);
    }

    public static function disablePooling(object $container, string $serviceId): void
    {
        self::memoryPool($container)->disable($serviceId);
    }

    public static function isPoolingEnabled(object $container, string $serviceId): bool
    {
        return self::memoryPool($container)->isEnabled($serviceId);
    }

    public static function releaseToPool(object $container, string $serviceId, object $instance): void
    {
        self::memoryPool($container)->release($serviceId, $instance);
    }

    public static function clearPool(object $container, string $serviceId): void
    {
        self::memoryPool($container)->clear($serviceId);
    }

    public static function clearAllPools(object $container): void
    {
        self::memoryPool($container)->clearAll();
    }

    /**
     * @return array{configured: bool, max_size: int, available: int}
     */
    public static function poolStats(object $container, string $serviceId): array
    {
        return self::memoryPool($container)->stats($serviceId);
    }

    public static function enableProfiling(object $container): void
    {
        self::profiling($container)->enable();
    }

    public static function disableProfiling(object $container): void
    {
        self::profiling($container)->disable();
    }

    public static function isProfilingEnabled(object $container): bool
    {
        return self::profiling($container)->isEnabled();
    }

    public static function resetProfile(object $container): void
    {
        self::profiling($container)->reset();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     sample_count: int,
     *     total_ms: float,
     *     by_operation: array<string, array{count: int, total_ms: float, avg_ms: float}>,
     *     top_slowest: list<array{operation: string, target: string, elapsed_ms: float, cached: bool}>
     * }
     */
    public static function profileReport(object $container, int $limit = 10): array
    {
        return self::profiling($container)->report($limit);
    }

    public static function cacheFor(object $container, string $serviceId, int $ttlSeconds): void
    {
        self::smartCache($container)->configureFor($serviceId, $ttlSeconds);
    }

    public static function cacheTagFor(object $container, string $tag, int $ttlSeconds): void
    {
        self::smartCache($container)->configureTagFor($tag, $ttlSeconds);
    }

    public static function forget(object $container, string $serviceId): void
    {
        $resolved = self::resolved($container);
        $resolvedId = self::aliasResolver($container)->resolve($serviceId);
        self::smartCache($container)->forget($resolvedId, $resolved);
        self::writeResolved($container, $resolved);
    }

    public static function forgetTag(object $container, string $tag): void
    {
        /** @var array<string, list<string>> $tags */
        $tags = self::readProperty($container, 'tags');
        $serviceIds = [];

        foreach ($tags[$tag] ?? [] as $serviceId) {
            $serviceIds[] = self::aliasResolver($container)->resolve($serviceId);
        }

        $resolved = self::resolved($container);
        self::smartCache($container)->forgetMany($serviceIds, $resolved);
        self::writeResolved($container, $resolved);
    }

    public static function forgetAll(object $container): void
    {
        $resolved = self::resolved($container);
        self::smartCache($container)->forgetAll($resolved);
        self::writeResolved($container, $resolved);
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
    public static function cacheStats(object $container, string $serviceId): array
    {
        $resolvedId = self::aliasResolver($container)->resolve($serviceId);
        $resolved = self::resolved($container);

        return self::smartCache($container)->stats(
            $resolvedId,
            self::tagsForService($container, $resolvedId),
            $resolved,
        );
    }

    private static function memoryPool(object $container): ContainerMemoryPoolSupport
    {
        /** @var ContainerMemoryPoolSupport $support */
        $support = self::readProperty($container, 'memoryPool');

        return $support;
    }

    private static function profiling(object $container): ContainerProfilingSupport
    {
        /** @var ContainerProfilingSupport $support */
        $support = self::readProperty($container, 'profiling');

        return $support;
    }

    private static function smartCache(object $container): ContainerSmartCacheSupport
    {
        /** @var ContainerSmartCacheSupport $support */
        $support = self::readProperty($container, 'smartCache');

        return $support;
    }

    private static function aliasResolver(object $container): ServiceAliasResolver
    {
        /** @var ServiceAliasResolver $resolver */
        $resolver = self::readProperty($container, 'aliasResolver');

        return $resolver;
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolved(object $container): array
    {
        /** @var array<string, mixed> $resolved */
        $resolved = self::readProperty($container, 'resolved');

        return $resolved;
    }

    /**
     * @param array<string, mixed> $resolved
     */
    private static function writeResolved(object $container, array $resolved): void
    {
        self::writeProperty($container, 'resolved', $resolved);
    }

    /**
     * @return list<string>
     */
    private static function tagsForService(object $container, string $serviceId): array
    {
        /** @var array<string, list<string>> $tags */
        $tags = self::readProperty($container, 'tags');
        $serviceTags = [];

        foreach ($tags as $tag => $taggedIds) {
            if (\in_array($serviceId, $taggedIds, true)) {
                $serviceTags[] = $tag;
            }
        }

        return $serviceTags;
    }

    private static function readProperty(object $object, string $name): mixed
    {
        $property = self::findProperty($object, $name);

        return $property->getValue($object);
    }

    private static function writeProperty(object $object, string $name, mixed $value): void
    {
        $property = self::findProperty($object, $name);
        $property->setValue($object, $value);
    }

    private static function findProperty(object $object, string $name): ReflectionProperty
    {
        $reflection = new ReflectionObject($object);

        while (!$reflection->hasProperty($name)) {
            $parent = $reflection->getParentClass();

            if ($parent === false) {
                throw new RuntimeException(\sprintf('Свойство "%s" не найдено у %s.', $name, $object::class));
            }

            $reflection = $parent;
        }

        return $reflection->getProperty($name);
    }
}
