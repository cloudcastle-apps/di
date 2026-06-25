<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Union со встроенным типом перед object-типом.
 */
final readonly class IntLoggerOrClockService
{
    public function __construct(
        public int|LoggerInterface|Clock $dependency = new Clock(),
    ) {
    }
}
