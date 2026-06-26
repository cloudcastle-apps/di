<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

/**
 * Promoted property и обычное typed property в одном классе.
 */
final class PromotedAndPlainPropertyService
{
    public function __construct(
        private Clock $promoted,
    ) {
    }

    private Clock $plain;

    public function getPromoted(): Clock
    {
        return $this->promoted;
    }

    public function getPlain(): Clock
    {
        return $this->plain;
    }
}
