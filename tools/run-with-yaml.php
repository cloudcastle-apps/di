<?php

declare(strict_types=1);

/**
 * Запускает бинарник PHPUnit/Infection с ext-yaml, если расширение ещё не загружено.
 *
 * @param list<string> $argv
 */
$command = array_slice($argv, 1);

if ($command === []) {
    fwrite(STDERR, 'Usage: run-with-yaml.php <binary> [args...]' . PHP_EOL);

    exit(1);
}

$phpArgs = extension_loaded('yaml') ? [] : ['-d', 'extension=yaml'];
$escaped = array_map(static fn (string $part): string => escapeshellarg($part), array_merge([PHP_BINARY], $phpArgs, $command));
passthru(implode(' ', $escaped), $exitCode);

exit($exitCode);
