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
    /** Точность округления миллисекунд в отчёте */
    private const MILLISECONDS_PRECISION = 4;

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
            'elapsed_ms' => $elapsedMs,
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
                ];
            }

            ++$byOperation[$operation]['count'];
            $byOperation[$operation]['total_ms'] += $sample['elapsed_ms'];
        }

        /** @var array<string, array{count: int, total_ms: float, avg_ms: float}> $byOperationReport */
        $byOperationReport = [];

        foreach ($byOperation as $operation => $stats) {
            $byOperationReport[$operation] = [
                'count' => $stats['count'],
                'total_ms' => self::roundMilliseconds($stats['total_ms']),
                'avg_ms' => self::roundMilliseconds(
                    $this->averageMilliseconds($stats['total_ms'], $stats['count']),
                ),
            ];
        }

        $sorted = $this->samples;
        usort(
            $sorted,
            static fn (array $left, array $right): int => $right['elapsed_ms'] <=> $left['elapsed_ms'],
        );

        if ($limit > 0) {
            $sorted = \array_slice($sorted, 0, $limit);
        }

        $sorted = array_map(
            static fn (array $sample): array => [
                'operation' => $sample['operation'],
                'target' => $sample['target'],
                'elapsed_ms' => self::roundMilliseconds($sample['elapsed_ms']),
                'cached' => $sample['cached'],
            ],
            $sorted,
        );

        return [
            'sample_count' => \count($this->samples),
            'total_ms' => self::roundMilliseconds($totalMs),
            'by_operation' => $byOperationReport,
            'top_slowest' => $sorted,
        ];
    }

    /**
     * @infection-ignore-all
     */
    private static function roundMilliseconds(float $milliseconds): float
    {
        return round($milliseconds, self::MILLISECONDS_PRECISION);
    }

    /**
     * @infection-ignore-all
     */
    private function averageMilliseconds(float $totalMilliseconds, int $sampleCount): float
    {
        return $totalMilliseconds / (float) $sampleCount;
    }
}
