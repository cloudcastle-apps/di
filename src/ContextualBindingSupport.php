<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use Closure;
use CloudCastle\DI\Contract\ContextualBindingNeedsInterface;
use CloudCastle\DI\Contract\ContextualBindingRegistrarInterface;

/**
 * Поддержка runtime contextual binding: when/needs/give (#25, часть 2).
 *
 * Делегирует fluent API конфигуратору и хранит зарегистрированные правила в реестре.
 */
final class ContextualBindingSupport implements ContextualBindingRegistrarInterface
{
    private readonly ContextualBindingRegistry $registry;

    private readonly ContextualBindingConfigurator $configurator;

    /**
     * @param Closure(): void|null $assertMutable Проверка mutability контейнера перед register
     */
    public function __construct(
        private readonly ?Closure $assertMutable = null,
    ) {
        $this->registry = new ContextualBindingRegistry();
        $this->configurator = new ContextualBindingConfigurator($this);
    }

    /**
     * Начинает цепочку contextual binding для класса-потребителя.
     *
     * @param string $consumerClass FQCN класса (when)
     *
     * @return ContextualBindingNeedsInterface Следующий шаг fluent API — {@see needs()}
     */
    public function when(string $consumerClass): ContextualBindingNeedsInterface
    {
        return $this->configurator->when($consumerClass);
    }

    /**
     * Возвращает id сервиса для пары consumer/need, если правило зарегистрировано.
     *
     * @param string $consumerClass FQCN класса-потребителя
     * @param string $need FQCN зависимости или id сервиса
     *
     * @return string|null id сервиса или null, если contextual-привязка не найдена
     */
    public function contextualGive(string $consumerClass, string $need): ?string
    {
        return $this->registry->resolve($consumerClass, $need);
    }

    /**
     * Экспортирует все зарегистрированные contextual-привязки.
     *
     * @return array<string, array<string, string>> Карта consumerClass → need → id сервиса
     */
    public function exportContextualMap(): array
    {
        return $this->registry->exportContextualMap();
    }

    /**
     * {@inheritDoc}
     */
    public function registerContextualBinding(ContextualBinding $binding): void
    {
        if ($this->assertMutable instanceof Closure) {
            ($this->assertMutable)();
        }

        $this->registry->register($binding);
    }
}
