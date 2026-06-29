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

if (getenv('GITHUB_ACTIONS') === 'true' && !$usePrecoverage) {
    $infectionArgs[] = '--map-source-class-to-test';
}

$phpArgs = extension_loaded('yaml') ? [] : ['-d', 'extension=yaml'];

$initialTestPhpOptions = [];

if (!extension_loaded('yaml')) {
    $initialTestPhpOptions[] = '-d extension=yaml';
}

$coverageXmlDir = dirname(__DIR__) . '/var/coverage/coverage-xml';
$usePrecoverage = is_file($coverageXmlDir . '/index.xml');

if ($usePrecoverage) {
    $infectionArgs[] = '--skip-initial-tests';
    $infectionArgs[] = '--coverage=' . $coverageXmlDir;
} else {
    if (extension_loaded('xdebug')) {
        $initialTestPhpOptions[] = '-d xdebug.mode=coverage';
    } elseif (extension_loaded('pcov')) {
        $projectRoot = dirname(__DIR__);
        $initialTestPhpOptions[] = '-d pcov.enabled=1';
        $initialTestPhpOptions[] = '-d pcov.directory=' . $projectRoot . '/src';
    }

    $initialTestPhpOptions[] = '-d opcache.enable=0';
    $initialTestPhpOptions[] = '-d opcache.enable_cli=0';

    if ($initialTestPhpOptions !== []) {
        $infectionArgs[] = '--initial-tests-php-options=' . implode(' ', $initialTestPhpOptions);
    }
}

$escaped = array_map(
    static fn (string $part): string => escapeshellarg($part),
    array_merge([PHP_BINARY], $phpArgs, $infectionArgs),
);
passthru(implode(' ', $escaped), $exitCode);

exit($exitCode);
