<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use CloudCastle\DI\Contract\ContainerInterface;
use Iterator;

/**
 * Сервис с intersection ContainerInterface и Iterator.
 */
final readonly class ContainerIteratorIntersectionService
{
    public function __construct(
        public ContainerInterface&Iterator $dependency,
    ) {
    }
}
