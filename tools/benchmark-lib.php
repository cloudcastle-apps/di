<?php

declare(strict_types=1);

use CloudCastle\DI\Compiler\ContainerCompiler;
use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\CompiledContainerInterface;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\AuditService;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\MemoryLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;

/**
 * @param list<float> $sortedSamples миллисекунды, отсортированы по возрастанию
 */
function benchmark_percentile(array $sortedSamples, float $percentile): float
{
    $count = \count($sortedSamples);

    if ($count === 0) {
        return 0.0;
    }

    $index = (int) ceil(($percentile / 100) * $count) - 1;
    $index = max(0, min($count - 1, $index));

    return round($sortedSamples[$index], 4);
}

/**
 * @return array{
 *     label: string,
 *     iterations: int,
 *     budget_ms: float|null,
 *     memory_budget_mb: float|null,
 *     elapsed_ms: float,
 *     p50_ms: float,
 *     p95_ms: float,
 *     p99_ms: float,
 *     ops_sec: float,
 *     memory_peak_mb: float
 * }
 */
function benchmark_run_scenario(
    string $label,
    int $iterations,
    ?float $budgetSeconds,
    callable $setup,
    callable $iteration,
    ?float $memoryBudgetMb = null,
): array {
    $context = $setup();
    $samples = [];
    $startedAt = microtime(true);

    for ($index = 0; $index < $iterations; ++$index) {
        $sampleStartedAt = microtime(true);
        $iteration($context, $index);
        $samples[] = (microtime(true) - $sampleStartedAt) * 1000;
    }

    $elapsedMs = (microtime(true) - $startedAt) * 1000;
    sort($samples);

    return [
        'label' => $label,
        'iterations' => $iterations,
        'budget_ms' => $budgetSeconds !== null ? $budgetSeconds * 1000 : null,
        'memory_budget_mb' => $memoryBudgetMb,
        'elapsed_ms' => round($elapsedMs, 2),
        'p50_ms' => benchmark_percentile($samples, 50),
        'p95_ms' => benchmark_percentile($samples, 95),
        'p99_ms' => benchmark_percentile($samples, 99),
        'ops_sec' => round($iterations / max($elapsedMs / 1000, 1e-9), 2),
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    ];
}

/**
 * @return list<array{
 *     label: string,
 *     iterations: int,
 *     budget_ms: float|null,
 *     memory_budget_mb: float|null,
 *     elapsed_ms: float,
 *     p50_ms: float,
 *     p95_ms: float,
 *     p99_ms: float,
 *     ops_sec: float,
 *     memory_peak_mb: float
 * }>
 */
function benchmark_collect(): array
{
    $results = [];

    $results[] = benchmark_run_scenario(
        'get() из кэша',
        10000,
        0.5,
        static function (): Container {
            $container = new Container();
            $container->set('cached', new stdClass());

            return $container;
        },
        static function (Container $container): void {
            $container->get('cached');
        },
        32.0,
    );

    $results[] = benchmark_run_scenario(
        'has() зарегистрированного id',
        10000,
        0.5,
        static function (): Container {
            $container = new Container();
            $container->set('cached', new stdClass());

            return $container;
        },
        static function (Container $container): void {
            $container->has('cached');
        },
        32.0,
    );

    $results[] = benchmark_run_scenario(
        'set() новых сервисов',
        5000,
        0.5,
        static function (): Container {
            return new Container();
        },
        static function (Container $container, int $index): void {
            $container->set('dynamic.' . $index, new stdClass());
        },
        48.0,
    );

    $results[] = benchmark_run_scenario(
        'make() прототипов',
        5000,
        1.0,
        static function (): Container {
            $container = new Container();
            $container->set('proto', static fn (): stdClass => new stdClass());

            return $container;
        },
        static function (Container $container): void {
            $container->make('proto');
        },
        48.0,
    );

    $results[] = benchmark_run_scenario(
        'call() с явными параметрами',
        10000,
        0.75,
        static function (): Container {
            return new Container();
        },
        static function (Container $container, int $index): void {
            $container->call(static fn (int $value): int => $value, ['value' => $index]);
        },
        32.0,
    );

    $results[] = benchmark_run_scenario(
        'call() с autowire',
        2000,
        1.25,
        static function (): Container {
            $container = new Container();
            $container->enableAutowiring();

            return $container;
        },
        static function (Container $container): void {
            $container->call(static fn (SimpleService $service): string => $service::class);
        },
        48.0,
    );

    $results[] = benchmark_run_scenario(
        'bind() + get()',
        1000,
        0.75,
        static function (): Container {
            return new Container();
        },
        static function (Container $container, int $index): void {
            $container->set('t.' . $index, new stdClass());
            $container->bind('a.' . $index, 't.' . $index);
            $container->get('a.' . $index);
        },
        48.0,
    );

    $results[] = benchmark_run_scenario(
        'getTaggedIds() (200 id)',
        10000,
        6.0,
        static function (): Container {
            $container = new Container();

            for ($index = 0; $index < 200; ++$index) {
                $container->tag('h.' . $index, 'handlers');
            }

            return $container;
        },
        static function (Container $container): void {
            if ($container->getTaggedIds('handlers') === []) {
                throw new RuntimeException('Ожидались tagged ids.');
            }
        },
        64.0,
    );

    $results[] = benchmark_run_scenario(
        'bulk get() 4000 разрешений',
        4000,
        2.0,
        static function (): Container {
            $container = new Container();

            for ($index = 0; $index < 2000; ++$index) {
                $container->set('bulk.' . $index, static fn (): int => $index);
            }

            return $container;
        },
        static function (Container $container, int $index): void {
            $container->get('bulk.' . ($index % 2000));
        },
        64.0,
    );

    $results[] = benchmark_run_scenario(
        'холодный autowire get()',
        500,
        1.5,
        static function (): int {
            return 0;
        },
        static function (int $unused, int $index): void {
            $container = new Container();
            $container->enableAutowiring();
            $container->set('app.clock', new Clock());
            $container->autowire(RequiredClockService::class);
            $container->get(RequiredClockService::class);
        },
        96.0,
    );

    $results[] = benchmark_run_scenario(
        'contextual binding get()',
        2000,
        1.5,
        static function (): Container {
            $container = new Container();
            $container->set('memory.logger', new MemoryLogger());
            $container->set('default.logger', new FileLogger());
            $container->bind(LoggerInterface::class, 'default.logger');
            $container->when(ReportService::class)
                ->needs(LoggerInterface::class)
                ->give('memory.logger');
            $container->autowire(ReportService::class);
            $container->autowire(AuditService::class);

            return $container;
        },
        static function (Container $container, int $index): void {
            if ($index % 2 === 0) {
                $container->get(ReportService::class);
            } else {
                $container->get(AuditService::class);
            }
        },
        64.0,
    );

    $results[] = benchmark_run_scenario(
        'compiled get() (contextual)',
        5000,
        1.0,
        static function (): CompiledContainerInterface {
            return benchmark_create_compiled_contextual_container();
        },
        static function (CompiledContainerInterface $container, int $index): void {
            if ($index % 2 === 0) {
                $container->get(ReportService::class);
            } else {
                $container->get(AuditService::class);
            }
        },
        64.0,
    );

    $results[] = benchmark_run_scenario(
        'runtime vs compiled parity get()',
        1000,
        2.0,
        static fn (): array => benchmark_create_runtime_compiled_pair(),
        'benchmark_resolve_runtime_compiled_pair',
        96.0,
    );

    return $results;
}

/**
 * @return array{runtime: Container, compiled: CompiledContainerInterface}
 */
function benchmark_create_runtime_compiled_pair(): array
{
    return [
        'runtime' => benchmark_create_frozen_contextual_container(),
        'compiled' => benchmark_create_compiled_contextual_container(),
    ];
}

/**
 * @param array{runtime: Container, compiled: CompiledContainerInterface} $pair
 */
function benchmark_resolve_runtime_compiled_pair(array $pair, int $index): void
{
    if ($index % 2 === 0) {
        $pair['runtime']->get(ReportService::class);
        $pair['compiled']->get(ReportService::class);

        return;
    }

    $pair['runtime']->get(AuditService::class);
    $pair['compiled']->get(AuditService::class);
}

function benchmark_create_frozen_contextual_container(): Container
{
    $container = new Container();
    $container->set('memory.logger', new MemoryLogger());
    $container->set('default.logger', new FileLogger());
    $container->bind(LoggerInterface::class, 'default.logger');
    $container->when(ReportService::class)
        ->needs(LoggerInterface::class)
        ->give('memory.logger');
    $container->autowire(ReportService::class);
    $container->autowire(AuditService::class);
    $container->freeze();

    return $container;
}

function benchmark_create_compiled_contextual_container(): CompiledContainerInterface
{
    $path = sys_get_temp_dir() . '/cloudcastle_di_bench_' . uniqid('', true) . '.php';
    $className = 'CloudCastle\\DI\\Benchmark\\ContextualCompiled_' . str_replace('.', '', uniqid('', true));

    (new ContainerCompiler())->compile(
        benchmark_create_frozen_contextual_container(),
        $path,
        $className,
    );

    require $path;

    $instance = new $className();

    if (!$instance instanceof CompiledContainerInterface) {
        throw new RuntimeException(\sprintf('Compiled class "%s" must implement CompiledContainerInterface.', $className));
    }

    register_shutdown_function(static function () use ($path): void {
        if (is_file($path)) {
            unlink($path);
        }
    });

    return $instance;
}

/**
 * @param list<array{
 *     label: string,
 *     iterations: int,
 *     budget_ms: float|null,
 *     memory_budget_mb: float|null,
 *     elapsed_ms: float,
 *     p50_ms: float,
 *     p95_ms: float,
 *     p99_ms: float,
 *     ops_sec: float,
 *     memory_peak_mb: float
 * }> $results
 *
 * @return list<array{
 *     label: string,
 *     metric: string,
 *     iterations: int,
 *     budget_ms: float|null,
 *     memory_budget_mb: float|null,
 *     elapsed_ms: float,
 *     p50_ms: float,
 *     p95_ms: float,
 *     p99_ms: float,
 *     ops_sec: float,
 *     memory_peak_mb: float,
 *     limit_ms: float|null,
 *     min_ops_sec: float|null,
 *     memory_limit_mb: float|null
 * }>
 */
function benchmark_find_regressions(array $results, float $tolerance): array
{
    $failed = [];

    foreach ($results as $row) {
        if ($row['budget_ms'] !== null) {
            $limitMs = $row['budget_ms'] * $tolerance;

            if ($row['elapsed_ms'] > $limitMs) {
                $failed[] = $row + [
                    'metric' => 'elapsed_ms',
                    'limit_ms' => $limitMs,
                    'min_ops_sec' => null,
                    'memory_limit_mb' => null,
                ];
            }
        }

        if ($row['memory_budget_mb'] !== null) {
            $memoryLimitMb = $row['memory_budget_mb'] * $tolerance;

            if ($row['memory_peak_mb'] > $memoryLimitMb) {
                $failed[] = $row + [
                    'metric' => 'memory_peak_mb',
                    'limit_ms' => null,
                    'min_ops_sec' => null,
                    'memory_limit_mb' => $memoryLimitMb,
                ];
            }
        }
    }

    return $failed;
}

/**
 * @param list<array{
 *     label: string,
 *     iterations: int,
 *     budget_ms: float|null,
 *     memory_budget_mb: float|null,
 *     elapsed_ms: float,
 *     p50_ms: float,
 *     p95_ms: float,
 *     p99_ms: float,
 *     ops_sec: float,
 *     memory_peak_mb: float
 * }> $results
 *
 * @return array{
 *     generated_at: string,
 *     php_version: string,
 *     tolerance: float,
 *     scenarios: list<array{
 *         label: string,
 *         iterations: int,
 *         budget_ms: float|null,
 *         memory_budget_mb: float|null,
 *         elapsed_ms: float,
 *         p50_ms: float,
 *         p95_ms: float,
 *         p99_ms: float,
 *         ops_sec: float,
 *         memory_peak_mb: float
 *     }>,
 *     regressions: list<array<string, mixed>>
 * }
 */
function benchmark_build_report_payload(array $results, float $tolerance): array
{
    return [
        'generated_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
        'php_version' => PHP_VERSION,
        'tolerance' => $tolerance,
        'scenarios' => $results,
        'regressions' => benchmark_find_regressions($results, $tolerance),
    ];
}
