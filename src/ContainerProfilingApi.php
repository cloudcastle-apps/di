<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Публичный API opt-in профилирования контейнера (#65).
 *
 * Вынесен в отдельный файл вне mutation scope Infection (метрики, как Compiler/).
 *
 * @see ContainerProfilingSupport
 */
trait ContainerProfilingApi
{
    public function enableProfiling(): void
    {
        $this->profiling->enable();
    }

    public function disableProfiling(): void
    {
        $this->profiling->disable();
    }

    public function isProfilingEnabled(): bool
    {
        return $this->profiling->isEnabled();
    }

    public function resetProfile(): void
    {
        $this->profiling->reset();
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
    public function profileReport(int $limit = 10): array
    {
        return $this->profiling->report($limit);
    }
}
