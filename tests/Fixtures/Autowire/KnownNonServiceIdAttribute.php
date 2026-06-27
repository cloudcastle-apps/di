<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Attribute;

/**
 * Attribute без {@see \CloudCastle\DI\Contract\ServiceIdAttribute} для edge-case тестов reader.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class KnownNonServiceIdAttribute
{
}
