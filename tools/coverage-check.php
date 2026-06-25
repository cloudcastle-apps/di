<?php

declare(strict_types=1);

/**
 * Проверяет, что покрытие строк кода в clover-отчёте не ниже порога (по умолчанию 95%).
 */

const MIN_LINE_COVERAGE_PERCENT = 95.0;

$cloverPath = $argv[1] ?? 'var/coverage/clover.xml';

if (!is_file($cloverPath)) {
    fwrite(STDERR, sprintf('Файл отчёта покрытия не найден: %s%s', $cloverPath, PHP_EOL));

    exit(1);
}

$xml = simplexml_load_file($cloverPath);

if ($xml === false) {
    fwrite(STDERR, 'Не удалось разобрать clover.xml.' . PHP_EOL);

    exit(1);
}

$metrics = $xml->project->metrics ?? null;

if ($metrics === null) {
    fwrite(STDERR, 'В clover.xml отсутствуют метрики проекта.' . PHP_EOL);

    exit(1);
}

$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, 'Нет исполняемых строк для покрытия.' . PHP_EOL);

    exit(1);
}

$percentage = ($coveredStatements / $statements) * 100;

if ($percentage < MIN_LINE_COVERAGE_PERCENT) {
    fwrite(
        STDERR,
        sprintf(
            'Покрытие строк %.2f%% (%d/%d) — требуется не менее %.0f%%.%s',
            $percentage,
            $coveredStatements,
            $statements,
            MIN_LINE_COVERAGE_PERCENT,
            PHP_EOL,
        ),
    );

    exit(1);
}

echo sprintf(
    'Покрытие строк: %.2f%% (%d/%d) — порог %.0f%%.%s',
    $percentage,
    $coveredStatements,
    $statements,
    MIN_LINE_COVERAGE_PERCENT,
    PHP_EOL,
);
