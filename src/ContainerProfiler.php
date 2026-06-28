<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Сборщик замеров resolve/call контейнера (opt-in, #65).
 *
 * Хранит отдельные замеры каждой операции {@see ContainerProfilingSupport::measure()}.
 * Не выполняет I/O и не зависит от {@see Container} — только агрегирует массив samples.
 *
 * @see ContainerProfilingSupport
 * @see ContainerInterface::profileReport()
 */
final class ContainerProfiler
{
    /**
     * Накопленные замеры в порядке регистрации.
     *
     * @var list<array{
     *     operation: string,
     *     target: string,
     *     elapsed_ms: float,
     *     cached: bool
     * }>
     */
    private array $samples = [];

    /**
     * Регистрирует один замер операции контейнера.
     *
     * @param string $operation Тип операции: `get`, `make` или `call`
     * @param string $target Id сервиса (после alias) или описание callable из
     *                       {@see ContainerProfilingSupport::describeCallable()}
     * @param float $elapsedMs Длительность операции в миллисекундах
     * @param bool $cached `true`, если singleton уже был в кэше до resolve (только для `get`)
     */
    public function record(string $operation, string $target, float $elapsedMs, bool $cached = false): void
    {
        $this->samples[] = [
            'operation' => $operation,
            'target' => $target,
            'elapsed_ms' => round($elapsedMs, 4),
            'cached' => $cached,
        ];
    }

    /**
     * Очищает все накопленные замеры.
     *
     * Не влияет на флаг enabled в {@see ContainerProfilingSupport}.
     */
    public function reset(): void
    {
        $this->samples = [];
    }

    /**
     * Формирует отчёт с агрегатами по типу операции и top-N самых медленных замеров.
     *
     * @param int $limit Максимум записей в `top_slowest`; `0` — вернуть все замеры, отсортированные по убыванию времени
     *
     * @return array{
     *     sample_count: int,
     *     total_ms: float,
     *     by_operation: array<string, array{count: int, total_ms: float, avg_ms: float}>,
     *     top_slowest: list<array{operation: string, target: string, elapsed_ms: float, cached: bool}>
     * }
     */
    public function report(int $limit = 10): array
    {
        $byOperation = [];
        $totalMs = 0.0;

        foreach ($this->samples as $sample) {
            $totalMs += $sample['elapsed_ms'];
            $operation = $sample['operation'];

            if (!isset($byOperation[$operation])) {
                $byOperation[$operation] = [
                    'count' => 0,
                    'total_ms' => 0.0,
                    'avg_ms' => 0.0,
                ];
            }

            ++$byOperation[$operation]['count'];
            $byOperation[$operation]['total_ms'] += $sample['elapsed_ms'];
        }

        foreach ($byOperation as $operation => $stats) {
            $byOperation[$operation]['total_ms'] = round($stats['total_ms'], 4);
            $byOperation[$operation]['avg_ms'] = round(
                $stats['total_ms'] / (float) max($stats['count'], 1),
                4,
            );
        }

        $sorted = $this->samples;
        usort(
            $sorted,
            static fn (array $left, array $right): int => $right['elapsed_ms'] <=> $left['elapsed_ms'],
        );

        if ($limit > 0) {
            $sorted = \array_slice($sorted, 0, $limit);
        }

        return [
            'sample_count' => \count($this->samples),
            'total_ms' => round($totalMs, 4),
            'by_operation' => $byOperation,
            'top_slowest' => $sorted,
        ];
    }
}
