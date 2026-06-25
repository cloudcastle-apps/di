<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Psr\Container\ContainerInterface;

/**
 * Сервис с типом PSR-11 контейнера.
 */
final readonly class PsrContainerConsumer
{
    public function __construct(
        public ContainerInterface $container,
    ) {
    }
}
