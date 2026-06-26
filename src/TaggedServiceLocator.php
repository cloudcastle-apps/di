<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\NotFoundException;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * Доступ к сервисам одного тега по id с возможностью итерации.
 *
 * Снимок списка id фиксируется в конструкторе через {@see ContainerInterface::getTaggedIds()}.
 * {@see has()} дополнительно проверяет {@see ContainerInterface::has()} — id мог быть удалён
 * из definition после создания locator.
 *
 * Итерация через {@see getIterator()} делегирует {@see ContainerInterface::getTagged()}
 * и создаёт экземпляры для всех доступных id тега.
 *
 * @implements IteratorAggregate<string, mixed>
 *
 * @see Container::getTaggedLocator()
 */
final readonly class TaggedServiceLocator implements IteratorAggregate
{
    /**
     * Id сервисов тега на момент создания locator (порядок {@see ContainerInterface::tag()}).
     *
     * @var list<string>
     */
    private array $taggedIds;

    /**
     * @param ContainerInterface $container Контейнер-источник сервисов
     * @param string $tag Имя тега
     */
    public function __construct(
        private ContainerInterface $container,
        private string $tag,
    ) {
        $this->taggedIds = $container->getTaggedIds($tag);
    }

    /**
     * Проверяет, что id зарегистрирован в теге и доступен в контейнере.
     *
     * @param string $id Идентификатор сервиса
     *
     * @return bool `true`, если id в теге и {@see ContainerInterface::has($id)} возвращает `true`
     */
    public function has(string $id): bool
    {
        return \in_array($id, $this->taggedIds, true)
            && $this->container->has($id);
    }

    /**
     * Возвращает сервис по id внутри тега.
     *
     * @param string $id Идентификатор сервиса в теге
     *
     * @throws NotFoundException Если id не в теге или сервис недоступен в контейнере
     *
     * @return mixed Экземпляр сервиса
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException(\sprintf(
                'Сервис "%s" не найден в теге "%s".',
                $id,
                $this->tag,
            ));
        }

        return $this->container->get($id);
    }

    /**
     * Итерирует сервисы тега как карту id → экземпляр.
     *
     * @return Traversable<string, mixed> Порядок ключей — порядок {@see ContainerInterface::tag()}
     */
    #[Override]
    public function getIterator(): Traversable
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($this->container->getTagged($this->tag) as $id => $service) {
            yield $id => $service;
        }
    }
}
