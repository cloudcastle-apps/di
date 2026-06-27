<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Countable;
use Iterator;

/**
 * Сервис с intersection-типом (Iterator&Countable).
 */
final class IntersectionParameterService
{
    public function __construct(
        public Iterator&Countable $storage,
    ) {
    }
}
