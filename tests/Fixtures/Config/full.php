<?php

declare(strict_types=1);

use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;

return [
    'priority' => 5,
    'register_attributes' => [
        CustomServiceIdAttribute::class,
    ],
    'autowiring' => [
        'enabled' => true,
        'parameter_name' => true,
        'property' => true,
        'method' => true,
    ],
    'scan' => [
        [
            'directory' => dirname(__DIR__) . '/Autowire',
            'namespace' => 'CloudCastle\\DI\\Tests\\Fixtures\\Autowire',
        ],
    ],
    'services' => [
        'app.flag' => true,
        'lazy.logger' => [
            'class' => FileLogger::class,
            'lazy' => true,
        ],
    ],
    'autowire' => [
        FileLogger::class,
    ],
    'bind' => [
        'config.bind.abstract' => FileLogger::class,
    ],
    'aliases' => [
        'flag' => 'app.flag',
        'app.clock' => Clock::class,
    ],
    'tags' => [
        'logger' => [
            'lazy.logger',
        ],
    ],
];
