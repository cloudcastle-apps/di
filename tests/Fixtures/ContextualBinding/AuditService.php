<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\ContextualBinding;

use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;

/**
 * Другой потребитель — без contextual rule должен получить default binding.
 */
final class AuditService
{
    public function __construct(
        public LoggerInterface $logger,
    ) {
    }
}
