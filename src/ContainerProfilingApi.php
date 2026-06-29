<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Публичный API opt-in профилирования контейнера (#65).
 *
 * Делегирует в {@see ContainerProfilingSupport}. Подключается к {@see Container} через use-trait.
 *
 * @see ContainerInterface
 * @see ContainerProfilingSupport
 */
trait ContainerProfilingApi
{
    /**
     * {@inheritDoc}
     */
    public function enableProfiling(): void
    {
        $this->profiling->enable();
    }

    /**
     * {@inheritDoc}
     */
    public function disableProfiling(): void
    {
        $this->profiling->disable();
    }

    /**
     * {@inheritDoc}
     */
    public function isProfilingEnabled(): bool
    {
        return $this->profiling->isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function resetProfile(): void
    {
        $this->profiling->reset();
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *     enabled: bool,
     *     sample_count: int,
     *     total_ms: float,
     *     by_operation: array<string, array{count: int, total_ms: float, avg_ms: float}>,
     *     top_slowest: list<array{operation: string, target: string, elapsed_ms: float, cached: bool}>
     * }
     */
    public function profileReport(int $limit = 10): array
    {
        return $this->profiling->report($limit);
    }
}
