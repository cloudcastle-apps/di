<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use Closure;

/**
 * Opt-in профилирование {@see Container::get()}, {@see Container::make()}, {@see Container::call()} (#65).
 */
final class ContainerProfilingSupport
{
    private bool $enabled = false;

    private readonly ContainerProfiler $profiler;

    public function __construct()
    {
        $this->profiler = new ContainerProfiler();
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function reset(): void
    {
        $this->profiler->reset();
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
     * @param callable(): T $callback
     *
     * @return T
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
     * Человекочитаемое описание callable для отчёта.
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

        \assert(\is_object($callable));

        return $callable::class . '::__invoke';
    }
}
