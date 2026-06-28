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
        '--min-msi=94',
        '--min-covered-msi=94',
        '--logger-github',
    ],
    array_slice($argv, 1),
);

$phpArgs = extension_loaded('yaml') ? [] : ['-d', 'extension=yaml'];

if (!extension_loaded('yaml')) {
    $infectionArgs[] = '--initial-tests-php-options=-d extension=yaml';
}

$escaped = array_map(
    static fn (string $part): string => escapeshellarg($part),
    array_merge([PHP_BINARY], $phpArgs, $infectionArgs),
);
passthru(implode(' ', $escaped), $exitCode);

exit($exitCode);
