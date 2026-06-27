<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="80">
</p>

# ⚡ Быстрый старт

> От нуля до работающего контейнера за несколько минут.

---

## 📋 Требования

| | |
|---|---|
| **PHP** | ^8.1 (CI: 8.1–8.5) |
| **Composer** | 2.x |
| **Runtime** | `psr/container` ^2.0 (подтягивается автоматически) |
| **Опционально** | `ext-yaml` — для YAML-конфигурации |

---

## 📦 Установка

```bash
composer require cloudcastle/di:^1.8
```

---

## 1️⃣ Явная регистрация (PSR-11)

Самый простой путь — `set()` и `get()`:

```php
<?php

declare(strict_types=1);

use CloudCastle\DI\Container;

$container = new Container();

$container->set('config.timezone', 'Europe/Moscow');
$container->set('logger', new Psr\Log\NullLogger());
$container->set(
    'repository',
    static fn (Container $c) => new UserRepository($c->get('logger')),
);

$logger = $container->get('logger');
```

| Метод | Назначение |
|-------|------------|
| `set($id, $instance\|callable)` | Регистрация сервиса или фабрики |
| `get($id)` | Получение (singleton-кэш для фабрик) |
| `has($id)` | PSR-11: сервис **можно получить** — `set()`, alias, кэш **или** autowiring (глобальный / `autowire()`) |
| `hasDefinition($id)` | Есть **явная** регистрация — `set()`, `autowire()` или alias; **без** учёта только глобального autowiring по FQCN |

---

## 2️⃣ Autowiring

```php
$container = new Container();
$container->enableAutowiring();

// id = FQCN класса; зависимости — по типам конструктора
$userService = $container->get(App\Service\UserService::class);
```

### Property и method injection

```php
$container->enablePropertyAutowiring(); // typed properties
$container->enableMethodAutowiring();   // setter / inject-методы

// #[Inject] на свойстве или методе — без enableProperty/MethodAutowiring
$service = $container->get(App\Service\ReportService::class);
```

### Attributes и autowiring по имени

```php
use CloudCastle\DI\Attribute\Inject;

$container->set('app.clock', $clock);
$container->set('logger', $logger);
$container->enableAutowiring();
$container->enableParameterNameAutowiring(); // $logger → get('logger')

// #[Inject('app.clock')] на параметре конструктора
$service = $container->get(App\Service\ReportService::class);
```

👉 [Autowiring](Autowiring) · [Сканирование](Class-scanning)

---

## 3️⃣ Сканирование каталога

```php
$container->scan(__DIR__ . '/Services', 'App\\Services\\');
// instantiable-классы с namespace App\Services\ — через autowire()
// существующие set() не перезаписываются
```

---

## 4️⃣ Прототипы, alias и lazy

```php
// Прототип — новый объект каждый раз
$jobA = $container->make(Job::class);
$jobB = $container->make(Job::class); // !== $jobA

// Alias — интерфейс → id
$container->alias(LoggerInterface::class, 'logger');

// Lazy — создание при первом getValue()
$container->set('reports', $container->lazy(ReportGenerator::class));
```

👉 [Прототипы, alias и lazy](Prototypes-alias-lazy)

---

## 5️⃣ call(), bind(), afterResolving

```php
$container->enableAutowiring();

$container->bind(LoggerInterface::class, FileLogger::class);

$container->addDefinitions([
    'config' => require __DIR__ . '/config.php',
]);

$container->call(static fn (OrderService $s) => $s->processPending());

$container->afterResolving(CacheWarmer::class, static function ($id, $w, $c): void {
    $w->warm($c->get('config'));
});
```

👉 [call(), bind(), afterResolving](Call-bind-callbacks)

---

## 6️⃣ Теги и декораторы

```php
$container->tag('handler.email', 'handlers');
$container->tag('handler.sms', 'handlers');

foreach ($container->getTaggedIterator('handlers') as $handler) {
    $handler->run();
}

$locator = $container->getTaggedLocator('handlers');
$handler = $locator->get('handler.email');
```

👉 [Теги и декораторы](Tags-and-decorators)

---

## 7️⃣ Конфигурация из файлов

```php
use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Configuration\ConfigurationSource;

$configurator = new ContainerConfigurator();
$configurator->configure($container, [
    __DIR__ . '/config/services.php',
    new ConfigurationSource(__DIR__ . '/config/override.json', priority: 10),
]);

$container->freeze();
```

Форматы: **PHP** (по умолчанию), **JSON**, **YAML** (`ext-yaml`), **XML**.

👉 [Конфигурация](Configuration) · [Справочник параметров](Configuration-reference)

---

## 8️⃣ Composition root

Соберите граф в **одной точке** входа:

```php
function createContainer(): Container
{
    $container = new Container();
    $container->enableAutowiring();
    $container->scan(__DIR__ . '/../src/Application', 'App\\Application\\');
    $container->set(Psr\Log\LoggerInterface::class, new MonologLogger(...));
    $container->freeze();

    return $container;
}
```

> ⚠️ Не тяните `Container` в домен — см. [Анти-паттерны](Anti-patterns).

---

## 9️⃣ Глобальный реестр (опционально)

```php
use CloudCastle\DI\ContainerRegistry;

ContainerRegistry::set($container);
$service = ContainerRegistry::get()->get(App\Service\UserService::class);

// В тестах:
ContainerRegistry::reset();
```

👉 [Глобальный реестр](Global-registry)

---

## 🆔 Стили идентификаторов

| Стиль | Пример | Когда |
|-------|--------|-------|
| Произвольный ключ | `'logger'`, `'config.db'` | явный `set()` |
| FQCN | `App\Service\Mailer::class` | autowiring, `scan()` |

---

## ➡️ Дальше

| Тема | Ссылка |
|------|--------|
| Сравнение с 5 аналогами | [Comparison](Comparison) |
| Архитектура и схемы | [Architecture](Architecture) |
| Полный API | [API-reference](API-reference) |
| Примеры bootstrap | [Bootstrap](Bootstrap) |
| FAQ | [FAQ](FAQ) |
