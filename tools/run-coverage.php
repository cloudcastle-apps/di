<?php

declare(strict_types=1);

/**
 * PHPUnit с покрытием: pcov или xdebug в зависимости от доступного расширения.
 *
 * @param list<string> $argv
 */
$phpArgs = extension_loaded('yaml') ? [] : ['-d', 'extension=yaml'];

$coveragePhpOptions = [];

if (extension_loaded('pcov')) {
    $projectRoot = dirname(__DIR__);
    $coveragePhpOptions[] = '-d';
    $coveragePhpOptions[] = 'pcov.enabled=1';
    $coveragePhpOptions[] = '-d';
    $coveragePhpOptions[] = 'pcov.directory=' . $projectRoot . '/src';
} elseif (extension_loaded('xdebug')) {
    \putenv('XDEBUG_MODE=coverage');
    $coveragePhpOptions[] = '-d';
    $coveragePhpOptions[] = 'xdebug.mode=coverage';
}

$coveragePhpOptions[] = '-d';
$coveragePhpOptions[] = 'opcache.enable=0';
$coveragePhpOptions[] = '-d';
$coveragePhpOptions[] = 'opcache.enable_cli=0';

$phpunitArgs = [
    'vendor/bin/phpunit',
    '--configuration=phpunit.coverage.xml.dist',
    '--coverage-text',
    '--coverage-clover=var/coverage/clover.xml',
];

$escaped = array_map(
    static fn (string $part): string => escapeshellarg($part),
    array_merge(
        [PHP_BINARY],
        $phpArgs,
        $coveragePhpOptions,
        $phpunitArgs,
    ),
);
passthru(implode(' ', $escaped), $exitCode);

exit($exitCode);
