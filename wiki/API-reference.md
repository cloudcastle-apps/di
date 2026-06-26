# Справочник API

## `CloudCastle\DI\Container`

Финальный класс, реализующий `CloudCastle\DI\Contract\ContainerInterface`.

### `get(string $id): mixed`

Возвращает сервис по идентификатору.

**Порядок разрешения:**

1. Разрешение **alias** → конечный id.
2. Singleton-кэш (`resolved`) — если уже создан.
3. Явное определение (`set()`) — фабрика или экземпляр.
4. Autowiring — если id зарегистрирован через `autowire()` или включён глобальный autowiring и id — instantiable FQCN.
5. `NotFoundException` — если ничего не подошло.

Для фабрики (`callable`):

- вызывается **один раз**, в аргумент передаётся `$this`;
- результат кэшируется (кроме `null` — см. ниже);
- к id применяются зарегистрированные **декораторы** перед кэшированием.

**Исключения:**

| Исключение | Когда |
|------------|-------|
| `NotFoundException` | id не зарегистрирован и autowiring недоступен |
| `ContainerException` | ошибка autowiring, циклическая зависимость, несоздаваемый класс |

### `has(string $id): bool`

`true`, если сервис:

- зарегистрирован через `set()`;
- уже создан (есть в кэше);
- зарегистрирован как **alias**;
- **или** может быть создан через autowiring (`autowire()` / глобальный режим + instantiable class).

### `set(string $id, mixed $concrete): void`

Регистрирует сервис:

- готовый экземпляр или скаляр;
- фабрику `callable(ContainerInterface): mixed`.

При повторном `set()` с тем же id **сбрасывается** singleton-кэш.

Явный `set()` **имеет приоритет** над autowiring для того же id.

### `hasDefinition(string $id): bool`

`true`, если есть:

- регистрация через `set()` (включая callable);
- **или** явная регистрация через `autowire()`;
- **или** id зарегистрирован как **alias**.

Не учитывает только singleton-кэш без definition. Не означает, что `get()` уже вызывался.

### Autowiring

#### `enableAutowiring(): void`

Включает создание instantiable-классов по FQCN при `get()` без явного `set()`.

#### `disableAutowiring(): void`

Отключает глобальный autowiring. Классы, зарегистрированные через `autowire()`, остаются доступны.

#### `isAutowiringEnabled(): bool`

Состояние глобального autowiring.

#### `enableParameterNameAutowiring(): void`

Включает разрешение параметров по **имени** (`$logger` → id `'logger'`), если `has($name)`.

#### `disableParameterNameAutowiring(): void`

Отключает autowiring по имени параметра.

#### `isParameterNameAutowiringEnabled(): bool`

Состояние autowiring по имени параметра (по умолчанию `false`). Применяется к параметрам конструктора, свойствам и методам.

#### `enablePropertyAutowiring(): void`

Включает autowiring **typed properties** после конструктора. Свойства с `#[Inject]` / `#[Autowire]` внедряются **всегда**, независимо от флага.

#### `disablePropertyAutowiring(): void`

Отключает autowiring свойств по типу.

#### `isPropertyAutowiringEnabled(): bool`

Состояние autowiring свойств (по умолчанию `false`).

#### `enableMethodAutowiring(): void`

Включает вызов public/protected inject-методов (setter и др.) с параметрами. Методы с attributes вызываются **всегда**.

#### `disableMethodAutowiring(): void`

Отключает autowiring методов по умолчанию.

#### `isMethodAutowiringEnabled(): bool`

Состояние autowiring методов (по умолчанию `false`).

#### `autowire(string $className): void`

Регистрирует класс для autowiring (id = FQCN). Сбрасывает singleton-кэш для этого id.

**Исключение:** `ContainerException`, если класс не найден или не instantiable (abstract, interface, …).

#### `scan(string $directory, ?string $namespace = null): void`

Сканирует каталог и вызывает `autowire()` для каждого найденного instantiable-класса, **кроме** id с уже существующим `set()`.

**Исключение:** `ContainerException`, если каталог не найден.

Подробнее — [Сканирование классов](Class-scanning), [Autowiring](Autowiring).

### Прототипы, alias и lazy

#### `make(string $id): mixed`

Создаёт **новый** экземпляр при каждом вызове. Не заполняет singleton-кэш. Декораторы и alias — как у `get()`.

**Исключения:** те же, что у `get()`.

#### `alias(string $alias, string $targetId): void`

Регистрирует альтернативный id. `get($alias)` и `make($alias)` разрешают `$targetId` (с учётом цепочек).

**Исключение:** `ContainerException` при циклической цепочке alias.

#### `lazy(string $serviceId): LazyService`

Возвращает обёртку; первый `getValue()` вызывает `$container->get($serviceId)` и кэширует результат внутри `LazyService`.

Подробнее — [Прототипы, alias и lazy](Prototypes-alias-lazy).

### call(), bind(), addDefinitions(), afterResolving

#### `call(callable $callable, array $parameters = []): mixed`

Вызывает callable с autowiring параметров через `CallableInvoker` и `MemberResolver`.

**Поддерживаемые формы:** `Closure`, first-class callable, `[object, 'method']`, invokable-объект, имя глобальной функции.

**Порядок разрешения параметра:** явное значение в `$parameters` → PHP attributes → autowiring по имени (если включён) → разрешение по типу.

**Исключение:** `ContainerException`, если обязательный параметр не разрешается.

#### `bind(string $abstract, string $concrete): void`

| `$concrete` | Действие |
|------------|----------|
| instantiable class | `autowire($concrete)` + `alias($abstract, $concrete)` |
| зарегистрированный id / autowire / interface с autowiring | `alias($abstract, $concrete)` |
| иначе | `ContainerException` |

#### `addDefinitions(array $definitions): void`

Эквивалент цикла `set($id, $concrete)` для каждой пары массива. Сбрасывает singleton-кэш для каждого id, как `set()`.

#### `afterResolving(string $id, callable $callback): void`

`callable(string $id, mixed $instance, ContainerInterface $container): void` после **нового** создания при `get()` / `make()`.

- повторный `get()` из singleton-кэша — **без** callback;
- каждый `make()` — callback снова;
- несколько callback — порядок регистрации.

Подробнее — [call(), bind(), afterResolving](Call-bind-callbacks).

### Заморозка и интроспекция (v1.4)

#### `freeze(): void`

Запрещает изменение определений (`set`, `autowire`, `alias`, `tag`, `decorate`, `bind`, `scan`, `addDefinitions`, `afterResolving`, переключатели autowiring). `get()`, `make()`, `call()` работают. Идемпотентен.

#### `isFrozen(): bool`

`true` после `freeze()`.

#### `getDefinitionIds(): array`

`list<string>` — все id из definitions, autowire и alias (отсортированы, без дубликатов). Без создания сервисов.

#### `dump(): array`

Снимок состояния для отладки: `frozen`, `definitions`, `autowired`, `aliases`, `tags`, `decorators`, `resolved`, флаги autowiring. Без вызова `get()` для неразрешённых сервисов.

### Tagged services

#### `tag(string $id, string $tag): void`

Привязывает id к тегу. Повтор с тем же id и тегом не дублирует.

#### `getTagged(string $tag): array`

`array<string, mixed>` — сервисы с тегом в порядке `tag()`. Id без definition и без autowiring пропускаются.

#### `getTaggedIds(string $tag): array`

`list<string>` — только id в порядке `tag()`, **без** вызова `get()`.

#### `getTaggedIterator(string $tag): TaggedServiceIterator`

`IteratorAggregate` — только **значения** сервисов (порядок `tag()`). Пропускает недоступные id, как `getTagged()`.

#### `getTaggedLocator(string $tag): TaggedServiceLocator`

`get($id)` / `has($id)` внутри тега + итерация `id => instance` через `getIterator()`.

**Исключение:** `NotFoundException` из `TaggedServiceLocator::get()` для id вне тега или недоступного сервиса.

### Декораторы

#### `decorate(string $id, callable $decorator): void`

`(mixed $inner, ContainerInterface $container): mixed` — обёртка при `get()` и `make()`. Порядок: первый зарегистрированный декоратор ближе к inner. Сбрасывает singleton-кэш id.

---

## `CloudCastle\DI\CallableInvoker`

Внутренний класс; создаётся лениво в `Container::call()`.

Вызывает callable с autowiring параметров через `MemberResolver` (тот же путь, что у конструктора `Autowirer`).

| Метод | Описание |
|-------|----------|
| `invoke(callable, array $parameters = []): mixed` | Собирает аргументы и вызывает callable |

**Исключение:** `ContainerException` — неразрешимый параметр или некорректный callable.

---

## `CloudCastle\DI\AfterResolvingDispatcher`

Внутренний класс; хранит callback для `Container::afterResolving()`.

| Метод | Описание |
|-------|----------|
| `register(string $id, callable $callback): void` | Добавляет callback в очередь для id |
| `dispatch(string $id, mixed $instance, ContainerInterface $container): void` | Вызывает все callback для id |

---

## `CloudCastle\DI\TaggedServiceIterator`

`IteratorAggregate<int, mixed>` — итератор **значений** сервисов одного тега.

Создаётся через `Container::getTaggedIterator()`. При итерации делегирует `getTagged()` — недоступные id пропускаются.

### `getIterator(): Traversable<int, mixed>`

Экземпляры в порядке `tag()`, без ключей id.

---

## `CloudCastle\DI\TaggedServiceLocator`

`IteratorAggregate<string, mixed>` — доступ к сервисам тега по id.

Снимок id фиксируется в конструкторе (`getTaggedIds()`). Создаётся через `Container::getTaggedLocator()`.

| Метод | Описание |
|-------|----------|
| `has(string $id): bool` | id в теге **и** `container->has($id)` |
| `get(string $id): mixed` | `container->get($id)` |
| `getIterator(): Traversable<string, mixed>` | `id => instance` через `getTagged()` |

**Исключение:** `NotFoundException` из `get()` — id не в теге или сервис недоступен.

## `CloudCastle\DI\LazyService`

### `getValue(): mixed`

Откладывает `Container::get($serviceId)` до первого вызова; повторные вызовы возвращают тот же экземпляр из внутреннего кэша обёртки.

---

## `CloudCastle\DI\ServiceAliasResolver`

Внутренний класс; используется `Container::alias()`.

| Метод | Описание |
|-------|----------|
| `alias(string $alias, string $targetId): void` | Регистрация alias |
| `resolve(string $id): string` | Конечный id после цепочки |
| `isAlias(string $id): bool` | Зарегистрирован ли id как alias |

---

## `CloudCastle\DI\ServiceInstanceResolver`

Внутренний класс; создаёт экземпляры для `get()` / `make()` (с опциональным singleton-кэшированием).

---

## `CloudCastle\DI\Attribute\Inject`

PHP attribute для **параметра конструктора**, **свойства**, **метода** или **параметра метода**. Явный id: `#[Inject('app.clock')]`. Без id — fallback на autowiring по имени (если включён) и по типу.

## `CloudCastle\DI\Attribute\Autowire`

Аналог `Inject` с синтаксисом `#[Autowire(service: 'mailer')]`. Targets: `PARAMETER | PROPERTY | METHOD`.

---

## `CloudCastle\DI\Autowirer`

Внутренний класс; создаётся контейнером лениво.

### `instantiate(string $className): object`

Создаёт экземпляр: **конструктор → свойства → методы**. Обычно через `Container::get()`, не напрямую.

Связанные классы: `MemberResolver`, `PropertyInjector`, `MethodInjector`, `ParameterTypeResolver`.

---

## `CloudCastle\DI\ClassScanner`

### `scan(string $directory, ?string $namespace = null): array`

`list<string>` — FQCN instantiable-классов в каталоге. Используется `Container::scan()`.

**Исключение:** `ContainerException`, если каталог не существует.

---

## `CloudCastle\DI\ContainerRegistry`

| Метод | Описание |
|-------|----------|
| `set(ContainerInterface $container): void` | Устанавливает глобальный контейнер |
| `get(): ContainerInterface` | Возвращает контейнер |
| `has(): bool` | Инициализирован ли реестр |
| `reset(): void` | Сброс (тесты) |

**Исключение:** `ContainerException` из `get()`, если `set()` не вызывался.

---

## Контракт

```php
interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    public function set(string $id, mixed $concrete): void;
    public function hasDefinition(string $id): bool;

    public function tag(string $id, string $tag): void;
    /** @return array<string, mixed> */
    public function getTagged(string $tag): array;
    public function decorate(string $id, callable $decorator): void;

    public function enableAutowiring(): void;
    public function disableAutowiring(): void;
    public function isAutowiringEnabled(): bool;
    public function enableParameterNameAutowiring(): void;
    public function disableParameterNameAutowiring(): void;
    public function isParameterNameAutowiringEnabled(): bool;
    public function enablePropertyAutowiring(): void;
    public function disablePropertyAutowiring(): void;
    public function isPropertyAutowiringEnabled(): bool;
    public function enableMethodAutowiring(): void;
    public function disableMethodAutowiring(): void;
    public function isMethodAutowiringEnabled(): bool;
    public function autowire(string $className): void;
    public function scan(string $directory, ?string $namespace = null): void;
    public function make(string $id): mixed;
    public function alias(string $alias, string $targetId): void;
    public function lazy(string $serviceId): \CloudCastle\DI\LazyService;
    public function addDefinitions(array $definitions): void;
    public function bind(string $abstract, string $concrete): void;
    public function call(callable $callable, array $parameters = []): mixed;
    public function afterResolving(string $id, callable $callback): void;
    /** @return list<string> */
    public function getTaggedIds(string $tag): array;
    public function getTaggedIterator(string $tag): \CloudCastle\DI\TaggedServiceIterator;
    public function getTaggedLocator(string $tag): \CloudCastle\DI\TaggedServiceLocator;
}
```

---

## Исключения

| Класс | Когда |
|-------|-------|
| `NotFoundException` | `get()` — сервис недоступен |
| `ContainerException` | autowiring, alias, scan, registry, PSR-11 container error |

Обе реализуют интерфейсы PSR-11.

---

## Поведение по умолчанию

| Сценарий | Поведение |
|----------|-----------|
| Фабрика вызвана | результат кэшируется до `set()` / `decorate()` |
| `set('id', null)` | не считается регистрацией (`isset` в PHP) |
| Фабрика / autowire вернули `null` после декораторов | **не** кэшируется |
| Цикл A→B→A в **фабриках** | не обнаруживается |
| Цикл при **autowiring** | `ContainerException` |
| `make()` | новый экземпляр, кэш не заполняется |
| `afterResolving()` | только при новом создании, не из singleton-кэша |
| `make()` + `afterResolving()` | callback на каждый `make()` |
| Циклический **alias** | `ContainerException` при `alias()` или `resolve()` |
| `scan()` + существующий `set(FQCN)` | `set()` сохраняется, scan пропускает id |

Подробнее — [Фабрики и singleton](Factories-and-singleton), [Autowiring](Autowiring).
