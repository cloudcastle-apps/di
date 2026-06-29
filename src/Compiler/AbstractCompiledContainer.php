<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\CallableInvoker;
use CloudCastle\DI\ContainerMemoryPoolSupport;
use CloudCastle\DI\ContainerProfilingSupport;
use CloudCastle\DI\ContainerSmartCacheSupport;
use CloudCastle\DI\Contract\CompiledContainerInterface;
use CloudCastle\DI\Contract\ContextualBindingNeedsInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\LazyGhostProxyFactory;
use CloudCastle\DI\LazyService;
use CloudCastle\DI\ServiceAliasResolver;
use CloudCastle\DI\TaggedServiceIterator;
use CloudCastle\DI\TaggedServiceLocator;

/**
 * Базовый runtime-класс для compiled-контейнеров, сгенерированных {@see ContainerCompiler}.
 *
 * Определения зафиксированы на этапе компиляции; мутация API контейнера запрещена.
 * Создание сервисов делегируется подклассу через {@see create()}.
 *
 * @psalm-api Используется сгенерированными подклассами в deploy/build.
 *
 * @see CompiledContainerInterface
 */
abstract class AbstractCompiledContainer implements CompiledContainerInterface
{
    use \CloudCastle\DI\ContainerMemoryPoolApi;
    use \CloudCastle\DI\ContainerProfilingApi;
    use \CloudCastle\DI\ContainerSmartCacheApi;

    /** @var array<string, mixed> Singleton-кэш созданных экземпляров по resolved id */
    private array $resolved = [];

    /** Разрешение цепочек {@see alias()} */
    private readonly ServiceAliasResolver $aliasResolver;

    /** Ленивый {@see CallableInvoker} для {@see call()} */
    private ?CallableInvoker $callableInvoker = null;

    /** Opt-in профилирование get/make/call (#65) */
    private readonly ContainerProfilingSupport $profiling;

    /** Opt-in object pool для {@see make()} (#63) */
    private readonly ContainerMemoryPoolSupport $memoryPool;

    /** Opt-in TTL для singleton-кэша {@see get()} (#64) */
    private readonly ContainerSmartCacheSupport $smartCache;

    /**
     * Инициализирует compiled-контейнер снимком определений, собранным при компиляции.
     *
     * @param string $compiledClassName FQCN сгенерированного класса
     * @param array<string, string> $aliases Карта alias → target id
     * @param array<string, list<string>> $tags Карта тег → список id сервисов
     * @param list<string> $definitionIds Идентификаторы всех определений
     * @param array<string, array<string, string>> $contextual Contextual give по consumer FQCN
     * @param callable(): float|null $smartCacheClock Источник времени для smart cache (только тесты)
     */
    public function __construct(
        private readonly string $compiledClassName,
        array $aliases,
        private readonly array $tags,
        private readonly array $definitionIds,
        private readonly array $contextual = [],
        ?callable $smartCacheClock = null,
    ) {
        $this->aliasResolver = new ServiceAliasResolver();

        foreach ($aliases as $alias => $targetId) {
            $this->aliasResolver->alias($alias, $targetId);
        }

        $this->profiling = new ContainerProfilingSupport();
        $this->memoryPool = new ContainerMemoryPoolSupport();
        $this->smartCache = new ContainerSmartCacheSupport($smartCacheClock);
    }

    /**
     * Создаёт экземпляр сервиса по идентификатору (реализуется сгенерированным подклассом).
     *
     * @param string $id Resolved идентификатор сервиса
     *
     * @return mixed Новый экземпляр, скалярное значение или `null`
     */
    abstract protected function create(string $id): mixed;

    /**
     * {@inheritDoc}
     */
    public function getCompiledClassName(): string
    {
        return $this->compiledClassName;
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotFoundException Если сервис не зарегистрирован
     */
    public function get(string $id): mixed
    {
        $resolvedId = $this->aliasResolver->resolve($id);
        $this->smartCache->evictIfExpired(
            $resolvedId,
            $this->tagsForService($resolvedId),
            $this->resolved,
        );
        $wasCached = isset($this->resolved[$resolvedId]);

        return $this->profiling->trackGet(
            $resolvedId,
            $wasCached,
            function () use ($resolvedId, $id): mixed {
                if (isset($this->resolved[$resolvedId])) {
                    return $this->resolved[$resolvedId];
                }

                if (!$this->canCreate($resolvedId)) {
                    throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id));
                }

                return $this->resolveAndCache($resolvedId);
            },
        );
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        if ($this->aliasResolver->isAlias($id)) {
            return true;
        }

        $resolvedId = $this->aliasResolver->resolve($id);

        return isset($this->resolved[$resolvedId]) || $this->canCreate($resolvedId);
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotFoundException Если сервис не зарегистрирован
     */
    public function make(string $id): mixed
    {
        $resolvedId = $this->aliasResolver->resolve($id);

        return $this->memoryPool->make(
            $resolvedId,
            fn (): mixed => $this->profiling->trackMake(
                $resolvedId,
                function () use ($resolvedId, $id): mixed {
                    if (!$this->canCreate($resolvedId)) {
                        throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id));
                    }

                    return $this->create($resolvedId);
                },
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function hasDefinition(string $id): bool
    {
        return \in_array($id, $this->definitionIds, true) || $this->aliasResolver->isAlias($id);
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function tag(string $id, string $tag): void
    {
        $this->assertImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function getTagged(string $tag): array
    {
        /** @var array<string, mixed> $services */
        $services = [];

        foreach ($this->tags[$tag] ?? [] as $serviceId) {
            if (!$this->has($serviceId)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $services[$serviceId] = $this->get($serviceId);
        }

        return $services;
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function decorate(string $id, callable $decorator): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function enableAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function disableAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function isAutowiringEnabled(): bool
    {
        return false;
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function enableParameterNameAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function disableParameterNameAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function isParameterNameAutowiringEnabled(): bool
    {
        return false;
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function enablePropertyAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function disablePropertyAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function isPropertyAutowiringEnabled(): bool
    {
        return false;
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function enableMethodAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function disableMethodAutowiring(): void
    {
        $this->assertImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function isMethodAutowiringEnabled(): bool
    {
        return false;
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function registerAttribute(string $attributeClass): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function autowire(string $className): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function scan(string $directory, ?string $namespace = null): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function alias(string $alias, string $targetId): void
    {
        $this->assertImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function lazy(string $serviceId): LazyService
    {
        return new LazyService($this, $serviceId);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ContainerException Если symfony/var-exporter недоступен
     */
    public function lazyGhost(string $type, string $serviceId): object
    {
        /** @infection-ignore-all */
        if (!LazyGhostProxyFactory::isAvailable()) {
            throw new ContainerException('lazyGhost() требует symfony/var-exporter.'); // @codeCoverageIgnore
        }

        return LazyGhostProxyFactory::create(
            $this,
            $type,
            $this->aliasResolver->resolve($serviceId),
        );
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function addDefinitions(array $definitions): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function bind(string $abstract, string $concrete): void
    {
        $this->assertImmutable();
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function when(string $consumerClass): ContextualBindingNeedsInterface
    {
        $this->assertImmutable();

        throw new ContainerException('Contextual binding недоступен в compiled-контейнере.');
    }

    /**
     * {@inheritDoc}
     */
    public function contextualGive(string $consumerClass, string $need): ?string
    {
        return $this->contextual[$consumerClass][$need] ?? null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws ContainerException Если callable не разрешается
     */
    public function call(callable $callable, array $parameters = []): mixed
    {
        $target = ContainerProfilingSupport::describeCallable($callable);

        return $this->profiling->trackCall(
            $target,
            fn (): mixed => $this->callableInvoker()->invoke($callable, $parameters),
        );
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function afterResolving(string $id, callable $callback): void
    {
        $this->assertImmutable();
    }

    /**
     * {@inheritDoc}
     */
    public function getTaggedIds(string $tag): array
    {
        return $this->tags[$tag] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getTaggedIterator(string $tag): TaggedServiceIterator
    {
        return new TaggedServiceIterator($this, $tag);
    }

    /**
     * {@inheritDoc}
     */
    public function getTaggedLocator(string $tag): TaggedServiceLocator
    {
        return new TaggedServiceLocator($this, $tag);
    }

    /**
     * {@inheritDoc}
     */
    public function freeze(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function isFrozen(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinitionIds(): array
    {
        $ids = $this->definitionIds;
        sort($ids, SORT_STRING);

        return $ids;
    }

    /**
     * {@inheritDoc}
     */
    public function dump(): array
    {
        $definitions = $this->definitionIds;
        sort($definitions, SORT_STRING);

        return [
            'frozen' => true,
            'definitions' => $definitions,
            'autowired' => [],
            'aliases' => $this->aliasResolver->getAliases(),
            'tags' => $this->tags,
            'decorators' => [],
            'resolved' => array_keys($this->resolved),
            'autowiring' => [
                'enabled' => false,
                'parameterName' => false,
                'property' => false,
                'method' => false,
            ],
        ];
    }

    /**
     * Запрещено после компиляции.
     *
     * {@inheritDoc}
     *
     * @throws ContainerException Всегда: compiled-контейнер неизменяем
     */
    public function set(string $id, mixed $concrete): void
    {
        $this->assertImmutable();
    }

    /**
     * Проверяет, зарегистрирован ли сервис в снимке определений compiled-контейнера.
     *
     * @param string $id Resolved идентификатор сервиса
     *
     * @return bool `true`, если id присутствует в списке definitionIds
     */
    private function canCreate(string $id): bool
    {
        return \in_array($id, $this->definitionIds, true);
    }

    /**
     * Создаёт сервис через {@see create()} и кэширует singleton, если экземпляр не `null`.
     *
     * @param string $resolvedId Resolved идентификатор сервиса
     *
     * @return mixed Созданный экземпляр или скалярное значение
     */
    private function resolveAndCache(string $resolvedId): mixed
    {
        /** @psalm-suppress MixedAssignment */
        $instance = $this->create($resolvedId);

        if ($instance !== null) {
            $this->resolved[$resolvedId] = $instance;
            $this->smartCache->touch($resolvedId);
        }

        return $instance;
    }

    /**
     * Возвращает ленивый {@see CallableInvoker}, общий для всех вызовов {@see call()}.
     *
     * @return CallableInvoker Invoker с autowiring через текущий контейнер
     */
    private function callableInvoker(): CallableInvoker
    {
        return $this->callableInvoker ??= new CallableInvoker(
            $this,
            new AttributeServiceIdReader(),
        );
    }

    /**
     * Блокирует мутацию определений compiled-контейнера.
     *
     * @throws ContainerException Всегда при попытке изменить определения
     */
    private function assertImmutable(): void
    {
        throw new ContainerException('Compiled container заморожен: изменение определений запрещено.');
    }
}
