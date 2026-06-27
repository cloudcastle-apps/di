<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Countable;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use RuntimeException;

/**
 * PSR-контейнер с поддержкой {@see Countable} для intersection-тестов.
 */
final class PsrCountableStub implements PsrContainerInterface, Countable
{
    public function get(string $id): mixed
    {
        throw new RuntimeException(\sprintf('not used: %s', $id));
    }
    public function has(string $id): bool
    {
        return $id !== '';
    }
    public function count(): int
    {
        return 1;
    }
}
