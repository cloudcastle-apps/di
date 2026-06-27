<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use IteratorAggregate;
use Traversable;

/**
 * Итератор экземпляров сервисов с указанным тегом (только значения).
 *
 * Порядок элементов совпадает с порядком {@see ContainerInterface::tag()} для тега.
 * Недоступные id (без definition и без autowiring) пропускаются — как в {@see ContainerInterface::getTagged()}.
 * При итерации для каждого id вызывается {@see ContainerInterface::get()}, то есть сервисы создаются eagerly.
 *
 * Для списка id без создания экземпляров используйте {@see ContainerInterface::getTaggedIds()}.
 *
 * @implements IteratorAggregate<int, mixed>
 *
 * @see Container::getTaggedIterator()
 */
final class TaggedServiceIterator implements IteratorAggregate
{
    /**
     * @param ContainerInterface $container Контейнер-источник сервисов
     * @param string $tag Имя тега
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $tag,
    ) {
    }

    /**
     * Возвращает итератор значений сервисов тега (без ключей id).
     *
     * @return Traversable<int, mixed> Экземпляры в порядке регистрации тега
     */
    public function getIterator(): Traversable
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($this->container->getTagged($this->tag) as $service) {
            yield $service;
        }
    }
}
