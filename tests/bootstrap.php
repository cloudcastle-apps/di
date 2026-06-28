<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/tools/benchmark-lib.php';

if (!function_exists('yaml_parse_file')) {
    fwrite(
        STDERR,
        'Для тестов требуется расширение ext-yaml (yaml_parse_file).' . PHP_EOL,
    );

    exit(1);
}
