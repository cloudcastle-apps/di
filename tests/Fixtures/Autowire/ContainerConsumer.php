<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Contract\ContainerInterface;

/**
 * Сервис с внедрением контейнера.
 */
final readonly class ContainerConsumer
{
    public function __construct(
        public ContainerInterface $container,
    ) {
    }
}
