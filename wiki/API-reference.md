# Справочник API

## `CloudCastle\DI\Container`

Финальный класс, реализующий `CloudCastle\DI\Contract\ContainerInterface`.

### `get(string $id): mixed`

Возвращает сервис по идентификатору.

**Порядок разрешения:**

1. Singleton-кэш (`resolved`) — если уже создан.
2. Явное определение (`set()`) — фабрика или экземпляр.
3. Autowiring — если id зарегистрирован через `autowire()` или включён глобальный autowiring и id — instantiable FQCN.
4. `NotFoundException` — если ничего не подошло.

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
- **или** явная регистрация через `autowire()`.

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

Подробнее — [Сканирование классов](Class-scanning.md), [Autowiring](Autowiring.md).

### Tagged services

#### `tag(string $id, string $tag): void`

Привязывает id к тегу. Повтор с тем же id и тегом не дублирует.

#### `getTagged(string $tag): array`

`array<string, mixed>` — сервисы с тегом в порядке `tag()`. Id без definition и без autowiring пропускаются.

### Декораторы

#### `decorate(string $id, callable $decorator): void`

`(mixed $inner, ContainerInterface $container): mixed` — обёртка при `get()`. Порядок: первый зарегистрированный декоратор ближе к inner. Сбрасывает singleton-кэш id.

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
}
```

---

## Исключения

| Класс | Когда |
|-------|-------|
| `NotFoundException` | `get()` — сервис недоступен |
| `ContainerException` | autowiring, scan, registry, PSR-11 container error |

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
| `scan()` + существующий `set(FQCN)` | `set()` сохраняется, scan пропускает id |

Подробнее — [Фабрики и singleton](Factories-and-singleton.md), [Autowiring](Autowiring.md).
