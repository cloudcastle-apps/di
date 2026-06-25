<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Autowire;

use Iterator;

/**
 * Реализация Iterator без Countable.
 */
final class IteratorOnly implements Iterator
{
    /** @var list<mixed> */
    private array $items;

    /** @var int<0, max> */
    private int $position = 0;

    /**
     * @param list<mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function current(): mixed
    {
        return $this->items[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
