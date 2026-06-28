<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Сборщик замеров resolve/call контейнера (opt-in, #65).
 */
final class ContainerProfiler
{
    /** @var list<array{operation: string, target: string, elapsed_ms: float, cached: bool}> */
    private array $samples = [];

    /**
     * @param string $operation Тип операции: `get`, `make`, `call`
     * @param string $target Id сервиса или описание callable
     * @param float $elapsedMs Длительность в миллисекундах
     * @param bool $cached Был ли singleton уже в кэше до resolve
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
     * Очищает накопленные замеры.
     */
    public function reset(): void
    {
        $this->samples = [];
    }

    /**
     * Формирует отчёт с агрегатами и top-N самых медленных операций.
     *
     * @param int $limit Максимум записей в `top_slowest`
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
