<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;

/**
 * Хранит и вызывает callback после успешного создания экземпляра сервиса.
 *
 * Используется {@see Container::afterResolving()}. Callback не вызывается при повторном
 * {@see ContainerInterface::get()}, если экземпляр уже в singleton-кэше. Каждый
 * {@see ContainerInterface::make()} снова создаёт экземпляр и снова вызывает callback.
 *
 * Для одного id допускается несколько callback; порядок вызова — порядок {@see register()}.
 *
 * @see Container::afterResolving()
 */
final class AfterResolvingDispatcher
{
    /**
     * Callback по id сервиса.
     *
     * @var array<string, list<callable(string, mixed, ContainerInterface): void>>
     */
    private array $callbacks = [];

    /**
     * Регистрирует callback для указанного id сервиса.
     *
     * Callback получает id, созданный экземпляр и контейнер.
     *
     * @param string $id Идентификатор сервиса (как передан в {@see Container::afterResolving()})
     * @param callable(string, mixed, ContainerInterface): void $callback
     */
    public function register(string $id, callable $callback): void
    {
        $this->callbacks[$id][] = $callback;
    }

    /**
     * Вызывает все callback, зарегистрированные для `$id`.
     *
     * @param string $id Идентификатор сервиса (конечный id после resolve alias)
     * @param mixed $instance Созданный экземпляр или скалярное значение сервиса
     * @param ContainerInterface $container Контейнер, выполнивший resolve
     */
    public function dispatch(string $id, mixed $instance, ContainerInterface $container): void
    {
        foreach ($this->callbacks[$id] ?? [] as $callback) {
            $callback($id, $instance, $container);
        }
    }

    /**
     * Проверяет, зарегистрирован ли хотя бы один after-resolving callback.
     *
     * @return bool `true`, если {@see register()} вызывался хотя бы раз
     */
    public function hasCallbacks(): bool
    {
        return $this->callbacks !== [];
    }
}
