<?php

declare(strict_types=1);

use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;

return [
    'bind' => [
        LoggerInterface::class => FileLogger::class,
    ],
    'autowiring' => [
        'enabled' => true,
    ],
];
