# Быстрый старт

## Требования

- PHP ^8.3
- `psr/container` ^2.0 (подтягивается автоматически)

## Установка

```bash
composer require cloudcastle/di
```

## Минимальный пример (явная регистрация)

```php
<?php

declare(strict_types=1);

use CloudCastle\DI\Container;

$container = new Container();

$container->set('config.timezone', 'Europe/Moscow');
$container->set('logger', new Psr\Log\NullLogger());

$timezone = $container->get('config.timezone');
$logger = $container->get('logger');
```

## Autowiring

```php
use CloudCastle\DI\Container;

$container = new Container();
$container->enableAutowiring();

// id = полное имя класса
$userService = $container->get(App\Service\UserService::class);
```

Класс `UserService` создаётся автоматически; зависимости разрешаются по типам, attributes и (опционально) по имени. Подробнее — [Autowiring](Autowiring).

### Property и method injection

```php
use CloudCastle\DI\Attribute\Inject;

$container->set('app.metrics', $metrics);
$container->enableAutowiring();
$container->enablePropertyAutowiring(); // typed properties
$container->enableMethodAutowiring();   // setter без attribute

// #[Inject] на свойстве или inject-методе — без enableProperty/MethodAutowiring
$service = $container->get(App\Service\ReportService::class);
```

### Attributes и autowiring по имени

```php
use CloudCastle\DI\Attribute\Inject;

$container->set('app.clock', $clock);
$container->set('logger', $logger);
$container->enableAutowiring();
$container->enableParameterNameAutowiring();

// #[Inject('app.clock')] или параметр $logger → get('logger')
$service = $container->get(App\Service\ReportService::class);
```

## Сканирование каталога

```php
$container->scan(__DIR__ . '/Services', 'App\\Services\\');
```

Регистрирует все instantiable-классы в каталоге с namespace `App\Services\`. Существующие `set()` не перезаписываются. Подробнее — [Сканирование классов](Class-scanning).

## Прототипы, alias и lazy

```php
// Прототип
$job = $container->make(Job::class);

// Alias интерфейса
$container->alias(LoggerInterface::class, 'logger');

// Lazy-обёртка
$container->set('reports', $container->lazy(ReportGenerator::class));
```

Подробнее — [Прототипы, alias и lazy](Prototypes-alias-lazy).

## call(), bind() и afterResolving (v1.3)

```php
$container->enableAutowiring();

// bind вместо autowire + alias
$container->bind(LoggerInterface::class, FileLogger::class);

// Массовая регистрация
$container->addDefinitions([
    'config' => require __DIR__ . '/config.php',
    'logger' => static fn () => new FileLogger(),
]);

// Вызов с autowiring
$container->call(static fn (OrderService $s) => $s->processPending());

// Пост-обработка после первого создания
$container->afterResolving(CacheWarmer::class, static function ($id, $w, $c): void {
    $w->warm($c->get('config'));
});
```

Подробнее — [call(), bind(), afterResolving](Call-bind-callbacks).

## Теги: iterator и locator (v1.3)

```php
$container->tag('handler.a', 'handlers');
$container->tag('handler.b', 'handlers');

$ids = $container->getTaggedIds('handlers'); // без get()

foreach ($container->getTaggedIterator('handlers') as $handler) {
    $handler->run();
}

$locator = $container->getTaggedLocator('handlers');
if ($locator->has('handler.a')) {
    $locator->get('handler.a');
}
```

Подробнее — [Теги и декораторы](Tags-and-decorators).

## Глобальный реестр

```php
use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerRegistry;

$container = new Container();
$container->enableAutowiring();
ContainerRegistry::set($container);

$service = ContainerRegistry::get()->get(App\Service\UserService::class);
```

Подробнее — [Глобальный реестр](Global-registry).

## Конфигурация из файлов (v1.5)

Вместо ручного `set()` / `scan()` можно описать wiring в PHP, JSON, YAML или XML и применить через `ContainerConfigurator`:

```php
use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Configuration\ConfigurationSource;

$configurator = new ContainerConfigurator();
$configurator->configure($container, [
    new ConfigurationSource(__DIR__ . '/config/services.php'),
    new ConfigurationSource(__DIR__ . '/config/override.json', priority: 10),
]);
```

Подробнее — [Конфигурация из файлов](Configuration).

## Идентификаторы сервисов

Идентификаторы — произвольные строки:

| Стиль | Пример | Когда |
|-------|--------|-------|
| Произвольный ключ | `'logger'`, `'config.db'` | явный `set()` |
| FQCN | `App\Service\Mailer::class` | autowiring, `scan()` |

## PSR-11

`CloudCastle\DI\Container` реализует:

- `Psr\Container\ContainerInterface` — `get()`, `has()`;
- `CloudCastle\DI\Contract\ContainerInterface` — `set()`, `hasDefinition()`, `make()`, `alias()`, `lazy()`, `call()`, `bind()`, `addDefinitions()`, `afterResolving()`, autowiring, tags, decorators.

```php
if ($container->has('logger')) {
    $logger = $container->get('logger');
}
```

`hasDefinition()` — регистрация **без** создания экземпляра:

```php
if ($container->hasDefinition('repository')) {
    // set() или autowire() есть, get() ещё не вызывался
}
```

## Composition root

Собирайте граф зависимостей в одной точке входа (bootstrap):

```php
function createContainer(): Container
{
    $container = new Container();
    $container->enableAutowiring();
    $container->scan(__DIR__ . '/../src/Application', 'App\\Application\\');

    $container->set(Psr\Log\LoggerInterface::class, new MonologLogger(...));

    return $container;
}
```

В домен передавайте готовые объекты через конструктор — см. [Анти-паттерны](Anti-patterns).

## Дальше

- [Конфигурация из файлов](Configuration)
- [call(), bind(), afterResolving](Call-bind-callbacks)
- [Autowiring](Autowiring)
- [Сканирование классов](Class-scanning)
- [Теги и декораторы](Tags-and-decorators)
- [Фабрики и singleton](Factories-and-singleton)
- [Прототипы, alias и lazy](Prototypes-alias-lazy)
- [Справочник API](API-reference)
