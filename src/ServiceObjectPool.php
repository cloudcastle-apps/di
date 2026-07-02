<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\PoolableInterface;
use CloudCastle\DI\Exception\ContainerException;

/**
 * Хранилище переиспользуемых экземпляров по id сервиса (#63).
 *
 * Не создаёт объекты самостоятельно — только acquire/release вокруг {@see Container::make()}.
 */
final class ServiceObjectPool
{
    /** Размер пула по умолчанию для {@see ContainerMemoryPoolSupport::enablePooling()} */
    public const DEFAULT_MAX_SIZE = 16;

    /**
     * Свободные экземпляры в порядке LIFO.
     *
     * @var array<string, list<object>>
     */
    private array $available = [];

    /** @var array<string, int> Максимальный размер пула (свободных экземпляров) по id сервиса */
    private array $maxSizes = [];

    /**
     * Включает пул для id с ограничением вместимости.
     *
     * @param string $serviceId Id сервиса
     * @param int $maxSize Максимум свободных экземпляров в пуле
     *
     * @throws ContainerException Если `$maxSize` меньше 1
     */
    public function configure(string $serviceId, int $maxSize = self::DEFAULT_MAX_SIZE): void
    {
        if ($maxSize < 1) {
            throw new ContainerException(\sprintf(
                'Максимальный размер пула для "%s" должен быть не меньше 1, получено %d.',
                $serviceId,
                $maxSize,
            ));
        }

        $this->maxSizes[$serviceId] = $maxSize;
    }

    /**
     * Отключает пул и удаляет накопленные экземпляры для id.
     *
     * @param string $serviceId Id сервиса
     */
    public function remove(string $serviceId): void
    {
        unset($this->maxSizes[$serviceId], $this->available[$serviceId]);
    }

    /**
     * Проверяет, включён ли пул для id.
     *
     * @param string $serviceId Id сервиса
     *
     * @return bool `true`, если для id вызывался {@see configure()}
     */
    public function isConfigured(string $serviceId): bool
    {
        return isset($this->maxSizes[$serviceId]);
    }

    /**
     * Возвращает экземпляр из пула или создаёт новый через фабрику.
     *
     * @param string $serviceId Id сервиса
     * @param callable(): mixed $factory Фабрика нового экземпляра, если пул пуст
     *
     * @return mixed Экземпляр из пула или результат `$factory`
     */
    public function acquire(string $serviceId, callable $factory): mixed
    {
        if (!$this->isConfigured($serviceId)) {
            return $factory();
        }

        $available = $this->available[$serviceId] ?? [];

        if ($available !== []) {
            $servicePool = $this->available[$serviceId];
            $instance = array_pop($servicePool);
            $this->available[$serviceId] = $servicePool;

            return $instance ?? $factory();
        }

        return $factory();
    }

    /**
     * Возвращает экземпляр в пул после {@see PoolableInterface::reset()}, если есть место.
     *
     * @param string $serviceId Id сервиса
     * @param object $instance Экземпляр, созданный через {@see acquire()}
     *
     * @throws ContainerException Если пул для id не включён
     */
    public function release(string $serviceId, object $instance): void
    {
        if (!$this->isConfigured($serviceId)) {
            throw new ContainerException(\sprintf(
                'Пул для сервиса "%s" не включён; вызовите enablePooling() перед releaseToPool().',
                $serviceId,
            ));
        }

        if ($instance instanceof PoolableInterface) {
            $instance->reset();
        }

        $maxSize = $this->maxSizes[$serviceId];
        $currentCount = \count($this->available[$serviceId] ?? []);

        if ($currentCount >= $maxSize) {
            return;
        }

        $this->available[$serviceId][] = $instance;
    }

    /**
     * Удаляет все свободные экземпляры для id, не отключая пул.
     *
     * @param string $serviceId Id сервиса
     */
    public function clear(string $serviceId): void
    {
        unset($this->available[$serviceId]);
    }

    /**
     * Удаляет все свободные экземпляры для каждого включённого id.
     */
    public function clearAll(): void
    {
        $this->available = [];
    }

    /**
     * Возвращает статистику пула для id сервиса.
     *
     * @param string $serviceId Id сервиса
     *
     * @return array{configured: bool, max_size: int, available: int} Флаги пула и число свободных экземпляров
     */
    public function stats(string $serviceId): array
    {
        if (!$this->isConfigured($serviceId)) {
            return [
                'configured' => false,
                'max_size' => 0,
                'available' => 0,
            ];
        }

        return [
            'configured' => true,
            'max_size' => $this->maxSizes[$serviceId],
            'available' => \count($this->available[$serviceId] ?? []),
        ];
    }
}
