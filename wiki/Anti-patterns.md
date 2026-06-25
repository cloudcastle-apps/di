# Анти-паттерны

Чего избегать при использовании CloudCastle DI.

## Service locator в домене

**Плохо:** передавать `Container` или `ContainerRegistry` в бизнес-классы и вызывать `get()` внутри методов.

**Лучше:** собирать объектный граф в composition root (bootstrap), в домен передавать готовые зависимости через конструктор.

```php
// composition root
$container->set('orders', static fn (ContainerInterface $c) => new OrderService(
    $c->get('repository'),
    $c->get('logger'),
));

$orderService = $container->get('orders');
```

Autowiring не отменяет это правило: `get()` в домене по-прежнему скрывает зависимости.

## Недоверенные идентификаторы

Не используйте пользовательский ввод напрямую как id сервиса:

```php
// опасно
$container->get($_GET['service']);
```

Идентификаторы должны быть константами или ключами из известной конфигурации.

## Злоупотребление глобальным реестром

`ContainerRegistry` удобен в bootstrap, но **не** заменяет явные зависимости:

| Допустимо | Избегать |
|-----------|----------|
| `ContainerRegistry::set()` в `index.php` / `bin/console` | `ContainerRegistry::get()` в каждом классе домена |
| `reset()` в PHPUnit `tearDown` | общий mutable state между тестами без reset |
| legacy-мigration с постепенным рефакторингом | новый код только через static `get()` |

**Лучше:** передавать `ContainerInterface` или конкретные сервисы в конструктор там, где это возможно.

## Слепое autowiring всего каталога

`scan()` без фильтра namespace регистрирует **все** instantiable-классы в дереве:

```php
// рискованно в большом src/
$container->scan(__DIR__ . '/src');
```

**Лучше:**

- фильтровать namespace: `scan($dir, 'App\\Services\\')`;
- явно переопределять критичные сервисы через `set()` после scan;
- не сканировать `vendor/`, `tests/`, generated code.

## Слепое autowiring по имени параметра

`enableParameterNameAutowiring()` связывает **имя параметра** с id сервиса. Включайте осознанно:

```php
// рискованно глобально без соглашения об именах id
$container->enableParameterNameAutowiring();
```

**Лучше:** явный `#[Inject('app.logger')]`, `set(LoggerInterface::class, …)` или точечные id через `set('logger', …)` без глобального режима by-name.

## Ожидание «магии» больших DI-фреймворков

CloudCastle DI **не** поддерживает:

- конфиг YAML / compiled container;
- autoconfigure и прочие возможности Symfony kernel.

**Поддерживается:** autowiring конструктора, **свойств** и **методов**; attributes; intersection; autowiring по имени — см. [Autowiring](Autowiring).

Не включайте `enablePropertyAutowiring()` / `enableMethodAutowiring()` глобально без необходимости — предпочитайте конструктор и явные attributes.

## Скрытые циклы в фабриках

Autowiring обнаруживает циклы A → B → A. **Фабрики** `set()` — нет:

```php
$container->set('a', static fn ($c) => new A($c->get('b')));
$container->set('b', static fn ($c) => new B($c->get('a'))); // бесконечный цикл
```

Разрывайте циклы на этапе проектирования или используйте lazy/proxy вручную.

## Сериализация контейнера

Не сериализуйте контейнер с замыканиями и runtime-состоянием. Собирайте граф заново при старте процесса.

## Сравнение с «большими» контейнерами

| CloudCastle DI | PHP-DI / Symfony |
|----------------|------------------|
| reflection autowiring (constructor, property, method), attributes, by-name, intersection | + autoconfigure, YAML |
| явный `set()` + опциональный scan | YAML/PHP config, compiler |
| компактный код, `psr/container` | полноценный DI фреймворк |
| `ContainerRegistry` опционален | часто интегрирован в kernel |

CloudCastle DI — баланс между минимализмом Pimple и возможностями «среднего» DI без лишних зависимостей.
