<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire\Scan;

/**
 * Enum в каталоге scan — не instantiable, пропускается при autowiring.
 */
enum ScannedStatus: string
{
    case Active = 'active';
}
