<?php

declare(strict_types=1);

/**
 * Запускает Infection; подключает ext-yaml только если расширение ещё не загружено.
 *
 * @param list<string> $argv
 */

/**
 * Precoverage Infection требует PHPUnit XML с привязкой строк к тестам ({@code <covered by=}).
 * PCOV на CI часто отдаёт только счётчики без имён тестов — тогда precoverage даёт ERRORED.
 */
function infectionCoverageXmlIncludesTestMetadata(string $directory): bool
{
    if (!is_file($directory . '/index.xml')) {
        return false;
    }

    $requiredProbes = [
        'ContainerMemoryPoolApi.php.xml' => 'ContainerMemoryPoolVisibilityTest',
        'ContainerProfilingApi.php.xml' => 'ContainerProfilingVisibilityTest',
        'ContainerSmartCacheApi.php.xml' => 'ContainerSmartCacheVisibilityTest',
    ];

    foreach ($requiredProbes as $file => $testClassFragment) {
        $path = $directory . '/' . $file;

        if (!is_file($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        if ($contents === false || !str_contains($contents, '<covered by=')) {
            return false;
        }

        if (!str_contains($contents, $testClassFragment)) {
            return false;
        }
    }

    return true;
}

$coverageXmlDir = dirname(__DIR__) . '/var/coverage/coverage-xml';
$usePrecoverage = infectionCoverageXmlIncludesTestMetadata($coverageXmlDir);

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

if (getenv('GITHUB_ACTIONS') === 'true' && extension_loaded('xdebug')) {
    $phpArgs = array_merge($phpArgs, ['-d', 'pcov.enabled=0', '-d', 'xdebug.mode=coverage']);
}

$initialTestPhpOptions = [];

if (!extension_loaded('yaml')) {
    $initialTestPhpOptions[] = '-d extension=yaml';
}

if ($usePrecoverage) {
    $infectionArgs[] = '--skip-initial-tests';
    $infectionArgs[] = '--coverage=' . $coverageXmlDir;
} else {
    if (getenv('GITHUB_ACTIONS') === 'true' && extension_loaded('xdebug')) {
        $initialTestPhpOptions[] = '-d pcov.enabled=0';
        $initialTestPhpOptions[] = '-d xdebug.mode=coverage';
    } elseif (extension_loaded('xdebug')) {
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
