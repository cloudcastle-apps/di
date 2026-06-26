#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Формирует markdown-отчёт по нагрузочным и performance-сценариям (фактическое время на текущей машине).
 *
 * Использование: php tools/benchmark-report.php [--markdown]
 */

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @return array{label: string, iterations: int, budget_ms: float|null, elapsed_ms: float}
 */
function run_benchmark(string $label, int $iterations, ?float $budgetSeconds, callable $callback): array
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
function collect_benchmarks(): array
{
  $results = [];

  $results[] = run_benchmark('get() из кэша', 10000, 0.5, static function (): void {
      $container = new Container();
      $container->set('cached', new stdClass());

      for ($i = 0; $i < 10000; ++$i) {
          $container->get('cached');
      }
  });

  $results[] = run_benchmark('has() зарегистрированного id', 10000, 0.5, static function (): void {
      $container = new Container();
      $container->set('cached', new stdClass());

      for ($i = 0; $i < 10000; ++$i) {
          $container->has('cached');
      }
  });

  $results[] = run_benchmark('set() новых сервисов', 5000, 0.5, static function (): void {
      $container = new Container();

      for ($i = 0; $i < 5000; ++$i) {
          $container->set('dynamic.' . $i, new stdClass());
      }
  });

  $results[] = run_benchmark('make() прототипов', 5000, 1.0, static function (): void {
      $container = new Container();
      $container->set('proto', static fn (): stdClass => new stdClass());

      for ($i = 0; $i < 5000; ++$i) {
          $container->make('proto');
      }
  });

  $results[] = run_benchmark('call() с явными параметрами', 10000, 0.75, static function (): void {
      $container = new Container();

      for ($i = 0; $i < 10000; ++$i) {
          $container->call(static fn (int $v): int => $v, ['v' => $i]);
      }
  });

  $results[] = run_benchmark('call() с autowire', 2000, 1.25, static function (): void {
      $container = new Container();
      $container->enableAutowiring();

      for ($i = 0; $i < 2000; ++$i) {
          $container->call(static fn (SimpleService $s): string => $s::class);
      }
  });

  $results[] = run_benchmark('bind() + get()', 1000, 0.75, static function (): void {
      $container = new Container();

      for ($i = 0; $i < 1000; ++$i) {
          $container->set('t.' . $i, new stdClass());
          $container->bind('a.' . $i, 't.' . $i);
          $container->get('a.' . $i);
      }
  });

  $results[] = run_benchmark('getTaggedIds() (200 id)', 10000, 0.35, static function (): void {
      $container = new Container();

      for ($i = 0; $i < 200; ++$i) {
          $container->tag('h.' . $i, 'handlers');
      }

      for ($i = 0; $i < 10000; ++$i) {
          $container->getTaggedIds('handlers');
      }
  });

  $results[] = run_benchmark('bulk get() 4000 разрешений', 4000, 2.0, static function (): void {
      $container = new Container();

      for ($i = 0; $i < 2000; ++$i) {
          $container->set('bulk.' . $i, static fn (): int => $i);
      }

      for ($i = 0; $i < 4000; ++$i) {
          $container->get('bulk.' . ($i % 2000));
      }
  });

  $results[] = run_benchmark('холодный autowire get()', 500, 1.5, static function (): void {
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

$markdown = \in_array('--markdown', $argv ?? [], true);
$results = collect_benchmarks();
$phpVersion = PHP_VERSION;
$date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');

if ($markdown) {
    echo "# Референсный прогон бенчмарков\n\n";
    echo "Среда: PHP {$phpVersion}, UTC {$date}. Пороги — из performance/load-тестов PHPUnit.\n\n";
    echo "| Сценарий | Итераций | Порог (мс) | Факт (мс) | Статус |\n";
    echo "|----------|----------|------------|-----------|--------|\n";

    foreach ($results as $row) {
        $budget = $row['budget_ms'] !== null ? (string) $row['budget_ms'] : '—';
        $status = $row['budget_ms'] === null || $row['elapsed_ms'] <= $row['budget_ms'] ? 'OK' : 'FAIL';

        echo \sprintf(
            "| %s | %d | %s | %.2f | %s |\n",
            $row['label'],
            $row['iterations'],
            $budget,
            $row['elapsed_ms'],
            $status,
        );
    }

    exit(0);
}

echo "Benchmark report (PHP {$phpVersion}, {$date})\n\n";

foreach ($results as $row) {
    $budget = $row['budget_ms'] !== null ? \sprintf(' / budget %.2f ms', $row['budget_ms']) : '';
    echo \sprintf(
        "- %s: %d iterations, %.2f ms%s\n",
        $row['label'],
        $row['iterations'],
        $row['elapsed_ms'],
        $budget,
    );
}
