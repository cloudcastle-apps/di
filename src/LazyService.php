<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;

/**
 * Откладывает создание сервиса до первого вызова {@see getValue()}.
 */
final class LazyService
{
    private mixed $value = null;

    private bool $initialized = false;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $serviceId,
    ) {
    }

    /**
     * Возвращает сервис, создавая его при первом обращении.
     */
    public function getValue(): mixed
    {
        if (!$this->initialized) {
            $this->value = $this->container->get($this->serviceId);
            $this->initialized = true;
        }

        return $this->value;
    }
}
