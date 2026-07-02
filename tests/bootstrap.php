<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('opcache.enable', '0');
ini_set('opcache.enable_cli', '0');

if (function_exists('opcache_reset')) {
    opcache_reset();
}

if (!function_exists('yaml_parse_file')) {
    fwrite(
        STDERR,
        'Для тестов требуется расширение ext-yaml (yaml_parse_file).' . PHP_EOL,
    );

    exit(1);
}
