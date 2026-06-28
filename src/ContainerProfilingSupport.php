<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use Closure;

/**
 * Opt-in профилирование {@see Container::get()}, {@see Container::make()} и {@see Container::call()} (#65).
 *
 * По умолчанию выключено — overhead нулевой, пока не вызван {@see enable()}.
 * Замеры делегируются в {@see ContainerProfiler}; отчёт доступен через {@see report()}.
 *
 * @see ContainerInterface::enableProfiling()
 * @see ContainerInterface::profileReport()
 */
final class ContainerProfilingSupport
{
    /** Включён ли сбор замеров */
    private bool $enabled = false;

    /** Хранилище замеров операций контейнера */
    private readonly ContainerProfiler $profiler;

    /**
     * Создаёт support с пустым профилировщиком (profiling выключен).
     */
    public function __construct()
    {
        $this->profiler = new ContainerProfiler();
    }

    /**
     * Включает сбор замеров для последующих {@see measure()} вызовов.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Отключает сбор замеров; накопленные samples сохраняются до {@see reset()}.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Проверяет, активен ли сбор замеров.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Сбрасывает накопленные замеры в {@see ContainerProfiler}.
     */
    public function reset(): void
    {
        $this->profiler->reset();
    }

    /**
     * Формирует отчёт профилировщика с флагом {@see isEnabled()}.
     *
     * @param int $limit Максимум записей в `top_slowest`; см. {@see ContainerProfiler::report()}
     *
     * @return array{
     *     enabled: bool,
     *     sample_count: int,
     *     total_ms: float,
     *     by_operation: array<string, array{count: int, total_ms: float, avg_ms: float}>,
     *     top_slowest: list<array{operation: string, target: string, elapsed_ms: float, cached: bool}>
     * }
     */
    public function report(int $limit = 10): array
    {
        return [
            'enabled' => $this->enabled,
            ...$this->profiler->report($limit),
        ];
    }

    /**
     * @template T
     *
     * @param callable(): T $resolver
     *
     * @return T
     */
    public function trackGet(string $resolvedId, bool $wasCached, callable $resolver): mixed
    {
        return $this->measure('get', $resolvedId, $resolver, $wasCached);
    }

    /**
     * @template T
     *
     * @param callable(): T $resolver
     *
     * @return T
     */
    public function trackMake(string $resolvedId, callable $resolver): mixed
    {
        return $this->measure('make', $resolvedId, $resolver);
    }

    /**
     * @template T
     *
     * @param callable(): T $invoker
     *
     * @return T
     */
    public function trackCall(string $target, callable $invoker): mixed
    {
        return $this->measure('call', $target, $invoker);
    }

    /**
     * Выполняет callback с опциональным замером времени.
     *
     * Если profiling выключен, callback вызывается без накладных расходов на замер.
     *
     * @template T
     *
     * @param string $operation Тип операции для отчёта: `get`, `make` или `call`
     * @param string $target Id сервиса или описание callable
     * @param callable(): T $callback Операция контейнера для выполнения
     * @param bool $cached Передаётся в {@see ContainerProfiler::record()} для операции `get`
     *
     * @return T Результат callback
     */
    public function measure(string $operation, string $target, callable $callback, bool $cached = false): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $startedAt = microtime(true);

        try {
            return $callback();
        } finally {
            $this->profiler->record(
                $operation,
                $target,
                (microtime(true) - $startedAt) * 1000.0,
                $cached,
            );
        }
    }

    /**
     * Возвращает человекочитаемое описание callable для поля `target` в отчёте.
     *
     * @param callable $callable Вызываемый объект, переданный в {@see Container::call()}
     *
     * @return string `closure`, имя функции, `Class::method` или `Class::__invoke`
     */
    public static function describeCallable(callable $callable): string
    {
        if ($callable instanceof Closure) {
            return 'closure';
        }

        if (\is_string($callable)) {
            return $callable;
        }

        if (\is_array($callable)) {
            $object = $callable[0];
            $method = $callable[1];
            $className = \is_object($object) ? $object::class : $object;

            return $className . '::' . $method;
        }

        /** @psalm-suppress RedundantCondition — сужение типа для PHPStan после string|array callable */
        if (\is_object($callable)) {
            return self::describeInvokableObject($callable);
        }

        return 'callable';
    }

    /**
     * @return non-empty-string
     */
    private static function describeInvokableObject(object $object): string
    {
        return $object::class . '::__invoke';
    }
}
