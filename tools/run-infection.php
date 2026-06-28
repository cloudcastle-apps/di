<?php

declare(strict_types=1);

/**
 * Запускает Infection; подключает ext-yaml только если расширение ещё не загружено.
 *
 * @param list<string> $argv
 */
$infectionArgs = array_merge(
    [
        'vendor/bin/infection',
        '--configuration=infection.json.dist',
        '--threads=1',
        '--min-msi=100',
        '--min-covered-msi=100',
        '--logger-github',
    ],
    array_slice($argv, 1),
);

$phpArgs = extension_loaded('yaml') ? [] : ['-d', 'extension=yaml'];

$initialTestPhpOptions = [];

if (!extension_loaded('yaml')) {
    $initialTestPhpOptions[] = '-d extension=yaml';
}

if (extension_loaded('pcov')) {
    $projectRoot = dirname(__DIR__);
    $initialTestPhpOptions[] = '-d pcov.enabled=1';
    $initialTestPhpOptions[] = '-d pcov.directory=' . $projectRoot . '/src';
}

if ($initialTestPhpOptions !== []) {
    $infectionArgs[] = '--initial-tests-php-options=' . implode(' ', $initialTestPhpOptions);
}

$coverageDirectory = dirname(__DIR__) . '/var/coverage/coverage-xml';

if (is_dir($coverageDirectory)) {
    $infectionArgs[] = '--coverage=' . $coverageDirectory;
    $infectionArgs[] = '--skip-initial-tests';
}

$escaped = array_map(
    static fn (string $part): string => escapeshellarg($part),
    array_merge([PHP_BINARY], $phpArgs, $infectionArgs),
);
passthru(implode(' ', $escaped), $exitCode);

exit($exitCode);
