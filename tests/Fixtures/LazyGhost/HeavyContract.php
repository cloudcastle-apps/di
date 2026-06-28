<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\LazyGhost;

interface HeavyContract
{
    public function work(): string;
}
