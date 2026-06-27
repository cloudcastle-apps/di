<?php

declare(strict_types=1);

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;

/**
 * @return array{label: string, iterations: int, budget_ms: float|null, elapsed_ms: float}
 */
function benchmark_run(string $label, int $iterations, ?float $budgetSeconds, callable $callback): array
{
    $startedAt = microtime(true);
    $callback();
    $elapsedMs = (microtime(true) - $startedAt) * 1000;

    return [
        'label' => $label,
        'iterations' => $iterations,
        'budget_ms' => $budgetSeconds !== null ? $budgetSeconds * 1000 : null,
        'elapsed_ms' => round($elapsedMs, 2),
    ];
}

/** @return list<array{label: string, iterations: int, budget_ms: float|null, elapsed_ms: float}> */
function benchmark_collect(): array
{
    $results = [];

    $results[] = benchmark_run('get() из кэша', 10000, 0.5, static function (): void {
        $container = new Container();
        $container->set('cached', new stdClass());

        for ($i = 0; $i < 10000; ++$i) {
            $container->get('cached');
        }
    });

    $results[] = benchmark_run('has() зарегистрированного id', 10000, 0.5, static function (): void {
        $container = new Container();
        $container->set('cached', new stdClass());

        for ($i = 0; $i < 10000; ++$i) {
            $container->has('cached');
        }
    });

    $results[] = benchmark_run('set() новых сервисов', 5000, 0.5, static function (): void {
        $container = new Container();

        for ($i = 0; $i < 5000; ++$i) {
            $container->set('dynamic.' . $i, new stdClass());
        }
    });

    $results[] = benchmark_run('make() прототипов', 5000, 1.0, static function (): void {
        $container = new Container();
        $container->set('proto', static fn (): stdClass => new stdClass());

        for ($i = 0; $i < 5000; ++$i) {
            $container->make('proto');
        }
    });

    $results[] = benchmark_run('call() с явными параметрами', 10000, 0.75, static function (): void {
        $container = new Container();

        for ($i = 0; $i < 10000; ++$i) {
            $container->call(static fn (int $v): int => $v, ['v' => $i]);
        }
    });

    $results[] = benchmark_run('call() с autowire', 2000, 1.25, static function (): void {
        $container = new Container();
        $container->enableAutowiring();

        for ($i = 0; $i < 2000; ++$i) {
            $container->call(static fn (SimpleService $s): string => $s::class);
        }
    });

    $results[] = benchmark_run('bind() + get()', 1000, 0.75, static function (): void {
        $container = new Container();

        for ($i = 0; $i < 1000; ++$i) {
            $container->set('t.' . $i, new stdClass());
            $container->bind('a.' . $i, 't.' . $i);
            $container->get('a.' . $i);
        }
    });

    $results[] = benchmark_run('getTaggedIds() (200 id)', 10000, 0.35, static function (): void {
        $container = new Container();

        for ($i = 0; $i < 200; ++$i) {
            $container->tag('h.' . $i, 'handlers');
        }

        for ($i = 0; $i < 10000; ++$i) {
            $container->getTaggedIds('handlers');
        }
    });

    $results[] = benchmark_run('bulk get() 4000 разрешений', 4000, 2.0, static function (): void {
        $container = new Container();

        for ($i = 0; $i < 2000; ++$i) {
            $container->set('bulk.' . $i, static fn (): int => $i);
        }

        for ($i = 0; $i < 4000; ++$i) {
            $container->get('bulk.' . ($i % 2000));
        }
    });

    $results[] = benchmark_run('холодный autowire get()', 500, 1.5, static function (): void {
        for ($i = 0; $i < 500; ++$i) {
            $container = new Container();
            $container->enableAutowiring();
            $container->set('app.clock', new Clock());
            $container->autowire(RequiredClockService::class);
            $container->get(RequiredClockService::class);
        }
    });

    return $results;
}

/**
 * @param list<array{label: string, iterations: int, budget_ms: float|null, elapsed_ms: float}> $results
 *
 * @return list<array{label: string, iterations: int, budget_ms: float|null, elapsed_ms: float, limit_ms: float}>
 */
function benchmark_find_regressions(array $results, float $tolerance): array
{
    $failed = [];

    foreach ($results as $row) {
        if ($row['budget_ms'] === null) {
            continue;
        }

        $limitMs = $row['budget_ms'] * $tolerance;

        if ($row['elapsed_ms'] > $limitMs) {
            $failed[] = $row + ['limit_ms' => $limitMs];
        }
    }

    return $failed;
}
