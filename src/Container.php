<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Contract\ContextualBindingNeedsInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use ReflectionClass;
use Throwable;

/**
 * Реализация DI-контейнера с singleton-фабриками, autowiring, тегами и декораторами.
 *
 * Разрешение сервиса в {@see get()} выполняется в порядке:
 * singleton-кэш → явное {@see set()} → autowiring ({@see autowire()} или глобальный режим).
 *
 * Autowiring делегируется {@see Autowirer}; массовая регистрация классов — {@see scan()}
 * через {@see ClassScanner}. Вызов callable — {@see call()} через {@see CallableInvoker}.
 * Пост-обработка resolve — {@see afterResolving()} через {@see AfterResolvingDispatcher}.
 * Глобальный доступ к контейнеру приложения — {@see ContainerRegistry}.
 *
 * @see ContainerInterface Контракт публичного API
 */
final class Container implements ContainerInterface
{
    use ContainerMemoryPoolApi;
    use ContainerProfilingApi;
    use ContainerSmartCacheApi;

    /** @var array<string, mixed> Определения сервисов: экземпляр, скаляр или фабрика */
    private array $definitions = [];

    /** @var array<string, mixed> Singleton-кэш созданных экземпляров (кроме `null`) */
    private array $resolved = [];

    /** @var array<string, list<string>> Порядок id для каждого тега */
    private array $tags = [];

    /** @var array<string, list<callable(mixed, ContainerInterface): mixed>> Цепочки декораторов по id */
    private array $decorators = [];

    /** @var array<string, true> FQCN, явно зарегистрированные через {@see autowire()} */
    private array $autowired = [];

    /** @var array<string, true> Id, находящиеся в текущей цепочке autowiring (детекция циклов) */
    private array $resolving = [];

    /** Включён ли autowiring любого instantiable FQCN при {@see get()} */
    private bool $autowiringEnabled = false;

    /** Включён ли autowiring по имени параметра конструктора */
    private bool $nameAutowiring = false;

    /** Включён ли autowiring типизированных свойств */
    private bool $propertyAutowiring = false;

    /** Включён ли autowiring методов с параметрами */
    private bool $methodAutowiring = false;

    /** Ленивый экземпляр {@see Autowirer}, общий для всех autowire-операций контейнера */
    private ?Autowirer $autowirer = null;

    /** Разрешение цепочек {@see alias()} и детекция циклов */
    private readonly ServiceAliasResolver $aliasResolver;

    /** Создание экземпляров, singleton-кэш, фабрики и декораторы для {@see get()} / {@see make()} */
    private readonly ServiceInstanceResolver $instanceResolver;

    /** Диспетчер callback {@see afterResolving()} после создания экземпляра */
    private readonly AfterResolvingDispatcher $resolveHooks;

    /** Ленивый {@see CallableInvoker} для {@see call()} */
    private ?CallableInvoker $callableInvoker = null;

    /** Запрет изменения определений после {@see freeze()} */
    private bool $frozen = false;

    /** Реестр PHP attributes для autowiring (встроенные и пользовательские) */
    private readonly AttributeServiceIdRegistry $attributeRegistry;

    /** Contextual when/needs/give (#25) */
    private readonly ContextualBindingSupport $contextual;

    /** Opt-in профилирование get/make/call (#65); по умолчанию выключено */
    private readonly ContainerProfilingSupport $profiling;

    /** Opt-in object pool для {@see make()} (#63); по умолчанию выключен */
    private readonly ContainerMemoryPoolSupport $memoryPool;

    /** Opt-in TTL для singleton-кэша {@see get()} (#64); по умолчанию без ограничения */
    private readonly ContainerSmartCacheSupport $smartCache;

    /**
     * Создаёт пустой контейнер с внутренними резолверами alias, экземпляров и after-resolving.
     *
     * @param callable(): float|null $smartCacheClock Источник времени для smart cache (только тесты)
     */
    public function __construct(?callable $smartCacheClock = null)
    {
        $this->aliasResolver = new ServiceAliasResolver();
        $this->instanceResolver = new ServiceInstanceResolver($this);
        $this->resolveHooks = new AfterResolvingDispatcher();
        $this->attributeRegistry = new AttributeServiceIdRegistry();
        $this->contextual = new ContextualBindingSupport(function (): void {
            $this->assertMutable();
        });
        $this->profiling = new ContainerProfilingSupport();
        $this->memoryPool = new ContainerMemoryPoolSupport();
        $this->smartCache = new ContainerSmartCacheSupport($smartCacheClock);
    }

    /**
     * Возвращает сервис по идентификатору.
     *
     * При первом обращении создаёт экземпляр (фабрика, autowiring), применяет декораторы
     * и кэширует результат. Повторный вызов с тем же id возвращает тот же объект.
     *
     * @param string $id Идентификатор сервиса или FQCN при autowiring
     *
     * @throws NotFoundException Если сервис недоступен
     * @throws ContainerException При ошибке autowiring или циклической зависимости
     *
     * @return mixed Экземпляр сервиса или зарегистрированное скалярное значение
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
            fn (): mixed => $this->resolveService($resolvedId, singleton: true),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function make(string $id): mixed
    {
        $resolvedId = $this->aliasResolver->resolve($id);

        return $this->memoryPool->make(
            $resolvedId,
            fn (): mixed => $this->profiling->trackMake(
                $resolvedId,
                fn (): mixed => $this->resolveService($resolvedId, singleton: false),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function alias(string $alias, string $targetId): void
    {
        $this->assertMutable();
        $this->aliasResolver->alias($alias, $targetId);
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
     */
    public function lazyGhost(string $type, string $serviceId): object
    {
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
     * {@inheritDoc}
     */
    public function addDefinitions(array $definitions): void
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($definitions as $id => $concrete) {
            $this->set($id, $concrete);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function bind(string $abstract, string $concrete): void
    {
        if (class_exists($concrete)) {
            $this->assertInstantiableClass($concrete);
            $this->autowire($concrete);
        } elseif (!interface_exists($concrete) && !$this->hasDefinition($concrete) && !$this->canAutowire($concrete)) {
            throw new ContainerException(\sprintf(
                'Нельзя привязать "%s" к "%s": цель не класс, не интерфейс и не зарегистрированный id.',
                $abstract,
                $concrete,
            ));
        }

        $this->alias($abstract, $concrete);
        $this->smartCache->forget($this->aliasResolver->resolve($concrete), $this->resolved);
    }

    /**
     * {@inheritDoc}
     */
    public function when(string $consumerClass): ContextualBindingNeedsInterface
    {
        $this->assertMutable();

        if (!class_exists($consumerClass)) {
            throw new ContainerException(\sprintf('Класс "%s" не найден.', $consumerClass));
        }

        return $this->contextual->when($consumerClass);
    }

    /**
     * {@inheritDoc}
     */
    public function contextualGive(string $consumerClass, string $need): ?string
    {
        return $this->contextual->contextualGive($consumerClass, $need);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function exportContextualBindings(): array
    {
        return $this->contextual->exportContextualMap();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function afterResolving(string $id, callable $callback): void
    {
        $this->assertMutable();
        $this->resolveHooks->register($id, $callback);
    }

    /**
     * {@inheritDoc}
     *
     * @return list<string>
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
        $this->frozen = true;
    }

    /**
     * {@inheritDoc}
     */
    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    /**
     * {@inheritDoc}
     *
     * @return list<string>
     */
    public function getDefinitionIds(): array
    {
        return $this->introspector()->definitionIds();
    }

    /**
     * {@inheritDoc}
     */
    public function dump(): array
    {
        return $this->introspector()->dump();
    }

    /**
     * Экспорт определений для компиляции (#24).
     *
     * @return array<string, mixed>
     *
     * @internal
     */
    public function exportDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @internal
     */
    public function hasAfterResolvingCallbacks(): bool
    {
        return $this->resolveHooks->hasCallbacks();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     parameterName: bool,
     *     property: bool,
     *     method: bool
     * }
     */
    private function autowiringFlags(): array
    {
        return [
            'enabled' => $this->autowiringEnabled,
            'parameterName' => $this->nameAutowiring,
            'property' => $this->propertyAutowiring,
            'method' => $this->methodAutowiring,
        ];
    }

    private function introspector(): ContainerIntrospector
    {
        return new ContainerIntrospector(
            $this->frozen,
            $this->definitions,
            $this->autowired,
            $this->aliasResolver->getAliases(),
            $this->tags,
            $this->decorators,
            $this->resolved,
            $this->autowiringFlags(),
        );
    }

    /**
     * Проверяет, доступен ли сервис для получения через {@see get()}.
     *
     * Учитывает явную регистрацию, singleton-кэш и возможность autowiring
     * (явный {@see autowire()} или глобальный режим + instantiable class).
     *
     * @param string $id Идентификатор сервиса или FQCN
     *
     * @return bool `true`, если {@see get()} не бросит NotFoundException
     */
    public function has(string $id): bool
    {
        if ($this->aliasResolver->isAlias($id)) {
            return true;
        }

        $id = $this->aliasResolver->resolve($id);

        return isset($this->definitions[$id])
            || isset($this->resolved[$id])
            || $this->canAutowire($id);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $id, mixed $concrete): void
    {
        $this->assertMutable();
        $this->smartCache->forget($id, $this->resolved);
        $this->definitions[$id] = $concrete;
    }

    /**
     * {@inheritDoc}
     */
    public function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id])
            || isset($this->autowired[$id])
            || $this->aliasResolver->isAlias($id);
    }

    /**
     * {@inheritDoc}
     */
    public function tag(string $id, string $tag): void
    {
        $this->assertMutable();

        $taggedIds = $this->tags[$tag] ?? [];

        if (!\in_array($id, $taggedIds, true)) {
            $taggedIds[] = $id;
            $this->tags[$tag] = $taggedIds;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress MixedAssignment
     */
    public function getTagged(string $tag): array
    {
        /** @var array<string, mixed> $services */
        $services = [];

        foreach ($this->tags[$tag] ?? [] as $id) {
            $resolvedId = $this->aliasResolver->resolve($id);

            if (!$this->hasDefinition($resolvedId) && !$this->canAutowire($resolvedId)) {
                continue;
            }

            $services[$id] = $this->get($id);
        }

        return $services;
    }

    /**
     * {@inheritDoc}
     */
    public function decorate(string $id, callable $decorator): void
    {
        $this->assertMutable();
        unset($this->resolved[$id]);
        $this->decorators[$id][] = $decorator;
    }

    /**
     * {@inheritDoc}
     */
    public function enableAutowiring(): void
    {
        $this->assertMutable();
        $this->autowiringEnabled = true;
    }

    /**
     * {@inheritDoc}
     */
    public function disableAutowiring(): void
    {
        $this->assertMutable();
        $this->autowiringEnabled = false;
    }

    /**
     * {@inheritDoc}
     */
    public function isAutowiringEnabled(): bool
    {
        return $this->autowiringEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function enableParameterNameAutowiring(): void
    {
        $this->assertMutable();
        $this->nameAutowiring = true;
    }

    /**
     * {@inheritDoc}
     */
    public function disableParameterNameAutowiring(): void
    {
        $this->assertMutable();
        $this->nameAutowiring = false;
    }

    /**
     * {@inheritDoc}
     */
    public function isParameterNameAutowiringEnabled(): bool
    {
        return $this->nameAutowiring;
    }

    /**
     * {@inheritDoc}
     */
    public function enablePropertyAutowiring(): void
    {
        $this->assertMutable();
        $this->propertyAutowiring = true;
    }

    /**
     * {@inheritDoc}
     */
    public function disablePropertyAutowiring(): void
    {
        $this->assertMutable();
        $this->propertyAutowiring = false;
    }

    /**
     * {@inheritDoc}
     */
    public function isPropertyAutowiringEnabled(): bool
    {
        return $this->propertyAutowiring;
    }

    /**
     * {@inheritDoc}
     */
    public function enableMethodAutowiring(): void
    {
        $this->assertMutable();
        $this->methodAutowiring = true;
    }

    /**
     * {@inheritDoc}
     */
    public function disableMethodAutowiring(): void
    {
        $this->assertMutable();
        $this->methodAutowiring = false;
    }

    /**
     * {@inheritDoc}
     */
    public function isMethodAutowiringEnabled(): bool
    {
        return $this->methodAutowiring;
    }

    /**
     * {@inheritDoc}
     */
    public function registerAttribute(string $attributeClass): void
    {
        $this->assertMutable();
        $this->attributeRegistry->register($attributeClass);
    }

    /**
     * {@inheritDoc}
     */
    public function autowire(string $className): void
    {
        $this->assertMutable();
        $this->assertInstantiableClass($className);
        unset($this->resolved[$className]);
        $this->autowired[$className] = true;
    }

    /**
     * {@inheritDoc}
     */
    public function scan(string $directory, ?string $namespace = null): void
    {
        $scanner = new ClassScanner();

        foreach ($scanner->scan($directory, $namespace) as $className) {
            if (!$this->hasDefinition($className)) {
                $this->autowire($className);
            }
        }
    }

    /**
     * Разрешает сервис через {@see ServiceInstanceResolver}.
     *
     * При новом создании вызывает callback {@see afterResolving()}.
     *
     * @param string $id Конечный id после разрешения alias
     * @param bool $singleton Сохранять ли экземпляр в singleton-кэше (`get()` — да, `make()` — нет)
     *
     * @throws NotFoundException Если сервис недоступен
     * @throws ContainerException При ошибке autowiring, фабрики или циклической зависимости
     *
     * @return mixed Экземпляр сервиса или скалярное значение
     */
    private function resolveService(string $id, bool $singleton): mixed
    {
        $wasCached = $singleton && isset($this->resolved[$id]);

        /** @psalm-suppress MixedAssignment */
        $instance = $this->instanceResolver->resolve(
            $id,
            $singleton,
            $this->definitions,
            $this->resolved,
            $this->resolving,
            $this->decorators,
            $this->canAutowire(...),
            $this->autowirer()->instantiate(...),
        );

        if (!$wasCached) {
            try {
                $this->resolveHooks->dispatch($id, $instance, $this);
            } catch (Throwable $exception) {
                if ($singleton) {
                    $this->smartCache->forget($id, $this->resolved);
                }

                throw $exception;
            }
        }

        if ($singleton && isset($this->resolved[$id]) && !$wasCached) {
            $this->smartCache->touch($id);
        }

        return $instance;
    }

    /**
     * Проверяет, можно ли создать сервис через autowiring для данного id.
     *
     * @param string $id Идентификатор или FQCN
     *
     * @return bool `true`, если id в {@see autowired} или (глобальный autowiring + instantiable class)
     */
    private function canAutowire(string $id): bool
    {
        if (($this->autowired[$id] ?? false) === true) {
            return true;
        }

        if (!$this->autowiringEnabled) {
            return false;
        }

        if (!class_exists($id)) {
            return false;
        }

        return (new ReflectionClass($id))->isInstantiable();
    }

    /**
     * Проверяет, что класс загружается и может быть создан через `new`.
     *
     * @param string $className Полное имя класса (class-string)
     *
     * @throws ContainerException Если класс не найден, abstract, interface или trait
     */
    private function assertInstantiableClass(string $className): void
    {
        if (!class_exists($className)) {
            throw new ContainerException(\sprintf('Класс "%s" не найден.', $className));
        }

        if (!(new ReflectionClass($className))->isInstantiable()) {
            throw new ContainerException(\sprintf('Класс "%s" нельзя создать через autowiring.', $className));
        }
    }

    /**
     * Возвращает общий экземпляр {@see Autowirer} для этого контейнера.
     *
     * @return Autowirer Создаётся при первом обращении
     */
    private function autowirer(): Autowirer
    {
        return $this->autowirer ??= new Autowirer(
            $this,
            new AttributeServiceIdReader($this->attributeRegistry),
        );
    }

    /**
     * Возвращает общий {@see CallableInvoker} для {@see call()}.
     *
     * @return CallableInvoker Создаётся при первом обращении
     */
    private function callableInvoker(): CallableInvoker
    {
        return $this->callableInvoker ??= new CallableInvoker(
            $this,
            new AttributeServiceIdReader($this->attributeRegistry),
        );
    }

    /**
     * @throws ContainerException Если контейнер заморожен
     */
    private function assertMutable(): void
    {
        if ($this->frozen) {
            throw new ContainerException('Контейнер заморожен: изменение определений запрещено.');
        }
    }
}
