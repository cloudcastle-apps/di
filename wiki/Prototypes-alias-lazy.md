# Прототипы, alias и lazy

Версия **1.2.0** добавляет три дополнения к базовому `get()` / `set()`:

> Схемы resolve, alias и lazy — [Архитектура](Architecture#get-и-make-общий-путь-разрешения).

| Метод | Назначение |
|-------|------------|
| `make(string $id)` | новый экземпляр **без** singleton-кэша |
| `alias(string $alias, string $targetId)` | альтернативный id → целевой сервис |
| `lazy(string $serviceId)` | отложенное создание через `LazyService` |

## `make()` — прототип вместо singleton

`get()` кэширует результат фабрики или autowiring. `make()` создаёт сервис заново при каждом вызове:

```php
$container->set('dto', static fn () => new stdClass());

$first = $container->make('dto');
$second = $container->make('dto');

// $first !== $second
```

Поведение:

- фабрика вызывается при **каждом** `make()`;
- singleton-кэш (`resolved`) **не** заполняется;
- декораторы применяются так же, как при `get()`;
- alias разрешается перед созданием;
- autowiring и циклы — те же правила, что у `get()`.

Типичные сценарии: DTO на запрос, временные объекты в тестах, stateful-объекты, которые не должны жить в singleton-кэше.

## `alias()` — несколько id на один сервис

```php
$container->set('app.clock', $clock);
$container->alias(ClockInterface::class, 'app.clock');

$container->get(ClockInterface::class); // тот же экземпляр, что get('app.clock')
```

Цепочки alias:

```php
$container->alias('clock.alias', 'app.clock');
$container->alias(ClockInterface::class, 'clock.alias');
```

При регистрации циклической цепочки (`a` → `b` → `a`) выбрасывается `ContainerException`.

`has()` и `hasDefinition()` возвращают `true` для зарегистрированного alias. `getTagged()` разрешает alias у id в теге.

С v1.3.0 для привязки интерфейса к классу удобнее **`bind(Interface::class, Implementation::class)`** — эквивалент `autowire()` + `alias()`. См. [call(), bind(), afterResolving](Call-bind-callbacks).

Внутренняя реализация alias — класс `ServiceAliasResolver`.

## `lazy()` — отложенное создание

```php
$container->set('reports', $container->lazy(ReportGenerator::class));

// ReportGenerator ещё не создан
$lazy = $container->get('reports');
$generator = $lazy->getValue(); // первый get() внутри LazyService
$same = $lazy->getValue();      // тот же экземпляр из кэша LazyService
```

`LazyService::getValue()`:

1. при первом вызове выполняет `$container->get($serviceId)`;
2. кэширует результат внутри обёртки;
3. при повторных вызовах возвращает тот же объект.

Удобно передавать «тяжёлые» зависимости через `set()`, не создавая их до первого использования.

## Сравнение `get()` и `make()`

| | `get()` | `make()` |
|---|---------|----------|
| Singleton-кэш | да | нет |
| Фабрика | один раз | каждый вызов |
| Autowiring | кэшируется | новый объект |
| Декораторы | да | да |
| Alias | да | да |

## См. также

- [Фабрики и singleton](Factories-and-singleton)
- [Справочник API](API-reference)
