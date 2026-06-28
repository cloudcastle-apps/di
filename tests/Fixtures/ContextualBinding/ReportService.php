<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\ContextualBinding;

use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;

/**
 * Потребитель с contextual rule для LoggerInterface.
 */
final class ReportService
{
    public function __construct(
        public LoggerInterface $logger,
    ) {
    }
}
