<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\ContextualBinding;

use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;

/**
 * Альтернативная реализация логгера для contextual binding.
 */
final class MemoryLogger implements LoggerInterface
{
}
