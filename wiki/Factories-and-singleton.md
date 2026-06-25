# Фабрики и singleton

## Готовый экземпляр

```php
$container->set('mailer', new Mailer($dsn));
```

## Фабрика (callable)

Если передан `callable`, он вызывается один раз; результат кэшируется до следующего `set()`:

```php
use CloudCastle\DI\Contract\ContainerInterface;

$container->set(
    'repository',
    static function (ContainerInterface $container): UserRepository {
        return new UserRepository($container->get('pdo'));
    },
);
```

В фабрику передаётся сам контейнер — так строятся цепочки зависимостей.

## Поддерживаемые callable

- замыкание `fn () => ...` или `function () { ... }`;
- объект с `__invoke`;
- first-class callable `$factory->create(...)`;
- массив `[$object, 'method']` (не путать с data-массивом сервиса).

## Повторная регистрация

`set()` с тем же id **сбрасывает** ранее созданный singleton:

```php
$container->set('token', 'dev');
$container->set('token', 'prod');

$container->get('token'); // 'prod'
```

## Ограничения

### `null` как значение

`set('id', null)` не распознаётся как регистрация из-за `isset()` в PHP.

### Фабрика, возвращающая `null`

Такой результат **не** кэшируется — при каждом `get()` фабрика вызывается снова.

### Циклические зависимости

A → B → A не обнаруживаются автоматически. Возможен бесконечный цикл или переполнение стека. Разрывайте циклы на этапе проектирования wiring.

## Сравнение `has()` и `hasDefinition()`

```php
$container->set('db', static fn () => new PDO(...));

$container->hasDefinition('db'); // true
$container->has('db');         // true
// get() ещё не вызывался — PDO не создан

$pdo = $container->get('db');
$container->has('db'); // true (есть и definition, и resolved)
```
