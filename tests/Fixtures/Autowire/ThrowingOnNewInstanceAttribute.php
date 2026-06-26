<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Attribute;
use RuntimeException;

/**
 * Attribute, выбрасывающий исключение при {@see \ReflectionAttribute::newInstance()}.
 */
#[Attribute]
final class ThrowingOnNewInstanceAttribute
{
    public function __construct()
    {
        throw new RuntimeException('Не должен создаваться при чтении постороннего attribute.');
    }
}
