<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Countable;
use Override;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use RuntimeException;

/**
 * PSR-контейнер с поддержкой {@see Countable} для intersection-тестов.
 */
final class PsrCountableStub implements PsrContainerInterface, Countable
{
    #[Override]
    public function get(string $id): mixed
    {
        throw new RuntimeException(\sprintf('not used: %s', $id));
    }

    #[Override]
    public function has(string $id): bool
    {
        return $id !== '';
    }

    #[Override]
    public function count(): int
    {
        return 1;
    }
}
