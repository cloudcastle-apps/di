# Справочник API

## `CloudCastle\DI\Container`

Финальный класс, реализующий `CloudCastle\DI\Contract\ContainerInterface`.

### `get(string $id): mixed`

Возвращает сервис по идентификатору.

- Если сервис уже создан (singleton-кэш) — возвращает кэшированный экземпляр.
- Если зарегистрирован `callable` — вызывает его **один раз**, передавая `$this`, кэширует результат.
- Если зарегистрирован объект или скаляр — возвращает как есть.

**Исключение:** `CloudCastle\DI\Exception\NotFoundException`, если id не зарегистрирован.

### `has(string $id): bool`

`true`, если сервис зарегистрирован **или** уже создан через `get()`.

### `set(string $id, mixed $concrete): void`

Регистрирует сервис:

- готовый экземпляр или значение;
- фабрику (`callable`), принимающую контейнер.

При повторном `set()` с тем же id **сбрасывается** singleton-кэш для этого id.

### `hasDefinition(string $id): bool`

`true`, если есть регистрация в конфигурации, без вызова фабрики и без учёта только кэша.

## Контракт

```php
interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    public function set(string $id, mixed $concrete): void;

    public function hasDefinition(string $id): bool;
}
```

## Исключения

| Класс | Когда |
|-------|-------|
| `NotFoundException` | `get()` для незарегистрированного id |
| `ContainerException` | базовое исключение контейнера (PSR-11) |

Обе реализуют соответствующие интерфейсы PSR-11.

## Поведение по умолчанию

| Сценарий | Поведение |
|----------|-----------|
| Фабрика вызвана | результат кэшируется до следующего `set()` |
| `set('id', null)` | не считается регистрацией (`isset` в PHP) |
| Фабрика вернула `null` | **не** кэшируется; каждый `get()` вызывает фабрику снова |
| Циклические зависимости A→B→A | не обнаруживаются; возможен бесконечный цикл |

Подробнее — [Фабрики и singleton](Factories-and-singleton).
