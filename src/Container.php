<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;
use Override;
use ReflectionClass;

/**
 * Реализация DI-контейнера с singleton-фабриками, autowiring, тегами и декораторами.
 *
 * Разрешение сервиса в {@see get()} выполняется в порядке:
 * singleton-кэш → явное {@see set()} → autowiring ({@see autowire()} или глобальный режим).
 *
 * Autowiring делегируется {@see Autowirer}; массовая регистрация классов — {@see scan()}
 * через {@see ClassScanner}. Глобальный доступ к контейнеру приложения — {@see ContainerRegistry}.
 *
 * @see ContainerInterface Контракт публичного API
 */
final class Container implements ContainerInterface
{
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
    #[Override]
    public function get(string $id): mixed
    {
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        if (isset($this->definitions[$id])) {
            return $this->resolveDefinition($id);
        }

        if ($this->canAutowire($id)) {
            return $this->resolveAutowired($id);
        }

        throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id));
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
    #[Override]
    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || isset($this->resolved[$id])
            || $this->canAutowire($id);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function set(string $id, mixed $concrete): void
    {
        unset($this->resolved[$id]);
        $this->definitions[$id] = $concrete;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->autowired[$id]);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function tag(string $id, string $tag): void
    {
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
    #[Override]
    public function getTagged(string $tag): array
    {
        /** @var array<string, mixed> $services */
        $services = [];

        foreach ($this->tags[$tag] ?? [] as $id) {
            if (!$this->hasDefinition($id) && !$this->canAutowire($id)) {
                continue;
            }

            $services[$id] = $this->get($id);
        }

        return $services;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function decorate(string $id, callable $decorator): void
    {
        unset($this->resolved[$id]);
        $this->decorators[$id][] = $decorator;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function enableAutowiring(): void
    {
        $this->autowiringEnabled = true;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function disableAutowiring(): void
    {
        $this->autowiringEnabled = false;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isAutowiringEnabled(): bool
    {
        return $this->autowiringEnabled;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function enableParameterNameAutowiring(): void
    {
        $this->nameAutowiring = true;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function disableParameterNameAutowiring(): void
    {
        $this->nameAutowiring = false;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isParameterNameAutowiringEnabled(): bool
    {
        return $this->nameAutowiring;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function enablePropertyAutowiring(): void
    {
        $this->propertyAutowiring = true;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function disablePropertyAutowiring(): void
    {
        $this->propertyAutowiring = false;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isPropertyAutowiringEnabled(): bool
    {
        return $this->propertyAutowiring;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function enableMethodAutowiring(): void
    {
        $this->methodAutowiring = true;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function disableMethodAutowiring(): void
    {
        $this->methodAutowiring = false;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function isMethodAutowiringEnabled(): bool
    {
        return $this->methodAutowiring;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function autowire(string $className): void
    {
        $this->assertInstantiableClass($className);
        unset($this->resolved[$className]);
        $this->autowired[$className] = true;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
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
     * Разрешает явно зарегистрированное определение: вызывает фабрику или возвращает значение.
     *
     * @param string $id Идентификатор сервиса
     *
     * @return mixed Экземпляр после {@see finalizeInstance()}
     *
     * @psalm-suppress MixedAssignment
     */
    private function resolveDefinition(string $id): mixed
    {
        $concrete = $this->definitions[$id];

        /** @var mixed $instance */
        $instance = \is_callable($concrete) ? $concrete($this) : $concrete;

        return $this->finalizeInstance($id, $instance);
    }

    /**
     * Создаёт сервис через {@see Autowirer} с отслеживанием циклических зависимостей.
     *
     * @param string $id FQCN создаваемого класса
     *
     * @throws ContainerException При обнаружении цикла в цепочке autowiring
     *
     * @return mixed Экземпляр после {@see finalizeInstance()}
     */
    private function resolveAutowired(string $id): mixed
    {
        if (($this->resolving[$id] ?? false) === true) {
            throw new ContainerException(\sprintf(
                'Обнаружена циклическая зависимость при autowiring сервиса "%s".',
                $id,
            ));
        }

        $this->resolving[$id] = true;

        try {
            $instance = $this->autowirer()->instantiate($id);

            return $this->finalizeInstance($id, $instance);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * Применяет декораторы к экземпляру и сохраняет его в singleton-кэш.
     *
     * Значение `null` не кэшируется — следующий {@see get()} создаст сервис заново.
     *
     * @param string $id Идентификатор сервиса
     * @param mixed $instance Inner-экземпляр до декораторов
     *
     * @return mixed Экземпляр после всех декораторов
     *
     * @psalm-suppress MixedAssignment
     */
    private function finalizeInstance(string $id, mixed $instance): mixed
    {
        foreach ($this->decorators[$id] ?? [] as $decorator) {
            $instance = $decorator($instance, $this);
        }

        if ($instance !== null) {
            $this->resolved[$id] = $instance;
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
        return $this->autowirer ??= new Autowirer($this);
    }
}
