# CloudCastle DI

**English:** Lightweight [PSR-11](https://www.php-fig.org/psr/psr-11/) dependency injection container for PHP 8.3+. Explicit `set()` / `get()` wiring, optional constructor/property/method autowiring, directory scan, tagged services, decorators, global registry — one runtime dependency (`psr/container`).

**Русский:** Лёгкий контейнер внедрения зависимостей для PHP 8.3+ с поддержкой PSR-11. Явная регистрация сервисов, singleton-фабрики, autowiring конструктора, **свойств** и **методов**, сканирование каталогов, теги, декораторы и глобальный реестр.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cloudcastle/di.svg)](https://packagist.org/packages/cloudcastle/di)
[![Total Downloads](https://img.shields.io/packagist/dt/cloudcastle/di.svg)](https://packagist.org/packages/cloudcastle/di)
[![PHP Version](https://img.shields.io/packagist/php-v/cloudcastle/di.svg)](https://packagist.org/packages/cloudcastle/di)
[![License](https://img.shields.io/packagist/l/cloudcastle/di.svg)](https://packagist.org/packages/cloudcastle/di)
[![Quality](https://github.com/cloudcastle-apps/di/actions/workflows/quality.yml/badge.svg)](https://github.com/cloudcastle-apps/di/actions/workflows/quality.yml)
[![Coverage](https://img.shields.io/badge/coverage-95%25+-brightgreen)](https://github.com/cloudcastle-apps/di/blob/main/CONTRIBUTING.md)
[![GitHub Discussions](https://img.shields.io/github/discussions/cloudcastle-apps/di)](https://github.com/cloudcastle-apps/di/discussions)

## Когда выбрать CloudCastle DI

| | CloudCastle DI | PHP-DI | Symfony DI | Pimple |
|---|:---:|:---:|:---:|:---:|
| PSR-11 | ✓ | ✓ | ✓ | частично |
| Autowiring (reflection) | ✓ | ✓ | ✓ | — |
| PHP attributes / by-name / intersection / property / method | ✓ | ✓ | ✓ | — |
| Явный `set()` API | ✓ | ✓ | ✓ | ✓ |
| Сканирование каталогов | ✓ | ✓ | ✓ | — |
| Tagged services / decorators | ✓ | ✓ | ✓ | — |
| Минимум зависимостей | ✓ (`psr/container`) | больше | фреймворк | ✓ |
| Подходит для micro-library / bootstrap | ✓ | ✓ | избыточен | ✓ |

Подходит, когда нужен **компактный контейнер** для composition root, тестов или небольшого приложения — с явным wiring и опциональной автоматикой без YAML и compiled container.

## Возможности

### Базовый DI

- Регистрация сервисов как готовых экземпляров или фабрик
- Singleton-поведение: фабрика вызывается один раз, результат кэшируется
- Передача контейнера в фабрику для разрешения зависимостей
- Соответствие PSR-11 (`Psr\Container\ContainerInterface`)

### Autowiring и сканирование

Порядок внедрения при autowiring: **конструктор → свойства → методы**.

- **`enableAutowiring()`** — создание классов по FQCN при `get()` без явного `set()`
- **`autowire(string $className)`** — точечная регистрация класса (id = полное имя класса)
- **`enableParameterNameAutowiring()`** — параметр `$logger` → сервис с id `'logger'` (по умолчанию выключен)
- **`enablePropertyAutowiring()`** / **`enableMethodAutowiring()`** — typed properties и inject-методы/setter после конструктора
- **`scan(string $directory, ?string $namespace = null)`** — обход каталога и autowiring найденных instantiable-классов
- PHP attributes **`Inject`** / **`Autowire`** на конструкторе, **свойствах** и **методах** (attributes работают без флагов property/method)
- Разрешение по типам: union, **intersection**, nullable, `ContainerInterface` / PSR-11
- Обнаружение циклических зависимостей при autowiring
- Явный `set()` всегда имеет **приоритет** над autowiring

### Расширения контракта

- **`tag()` / `getTagged()`** — групповое получение сервисов
- **`decorate()`** — цепочка декораторов при `get()`
- **`hasDefinition()`** — проверка регистрации без создания экземпляра

### Глобальный реестр

- **`ContainerRegistry::set()` / `get()` / `has()` / `reset()`** — singleton-контейнер приложения (инициализация в точке входа, `reset()` для тестов)

### Качество

- Строгая типизация, PHPStan max, Psalm level 1, покрытие строк ≥95%, Infection MSI ≥95%
- CI: PHP 8.3, 8.4, 8.5

## Требования

- PHP ^8.3
- `psr/container` ^2.0

## Установка

```bash
composer require cloudcastle/di:^1.1
```

## Быстрый старт

### Явная регистрация

```php
<?php

use CloudCastle\DI\Container;

$container = new Container();

$container->set('logger', new Psr\Log\NullLogger());
$container->set(
    'repository',
    static fn (Container $c) => new UserRepository($c->get('logger')),
);

$logger = $container->get('logger');
$repository = $container->get('repository');
```

### Autowiring конструктора

```php
use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\Container;

$container = new Container();
$container->set('app.clock', $clock);
$container->enableAutowiring();
$container->enableParameterNameAutowiring(); // опционально: $logger → id 'logger'

// Классы создаются по типам конструктора; id = FQCN
// #[Inject('app.clock')] на параметрах — явный id
$userService = $container->get(App\Service\UserService::class);
```

### Property и method injection

```php
use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\Container;

$container = new Container();
$container->set(LoggerInterface::class, $logger);
$container->enableAutowiring();
$container->enablePropertyAutowiring(); // typed properties без attribute
$container->enableMethodAutowiring();   // setter без attribute

// #[Inject] на свойстве или inject-методе — без enableProperty/MethodAutowiring
$service = $container->get(App\Service\ReportService::class);
```

### Сканирование каталога

```php
$container->scan(__DIR__ . '/Services', 'App\\Services\\');
// Каждый instantiable-класс в каталоге регистрируется через autowire()
// Существующие set() не перезаписываются
```

### Глобальный контейнер

```php
use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerRegistry;

$container = new Container();
$container->enableAutowiring();
ContainerRegistry::set($container);

$mailer = ContainerRegistry::get()->get(App\Mailer::class);
```

## API (кратко)

| Метод | Описание |
|-------|----------|
| `get(string $id): mixed` | Сервис из кэша, `set()`, autowiring или `NotFoundException` |
| `has(string $id): bool` | Доступен ли сервис (включая autowiring) |
| `set(string $id, mixed $concrete): void` | Экземпляр или фабрика; сбрасывает singleton-кэш |
| `hasDefinition(string $id): bool` | Есть `set()` или `autowire()` без создания |
| `tag()` / `getTagged()` | Группы сервисов по тегам |
| `decorate()` | Обёртки при `get()` |
| `enableAutowiring()` / `disableAutowiring()` / `isAutowiringEnabled()` | Глобальный autowiring |
| `enableParameterNameAutowiring()` / `disableParameterNameAutowiring()` | Autowiring по имени параметра |
| `enablePropertyAutowiring()` / `disablePropertyAutowiring()` | Autowiring typed properties |
| `enableMethodAutowiring()` / `disableMethodAutowiring()` | Autowiring inject-методов и setter |
| `autowire(string $className): void` | Явная регистрация класса |
| `scan(string $directory, ?string $namespace): void` | Autowiring классов из каталога |

Подробнее — [Wiki](https://github.com/cloudcastle-apps/di/wiki/Home) ( [Autowiring](https://github.com/cloudcastle-apps/di/wiki/Autowiring) · [API](https://github.com/cloudcastle-apps/di/wiki/API-reference) · [Bootstrap](https://github.com/cloudcastle-apps/di/wiki/Bootstrap) ) и `doc/guide/` после `composer docs`.

## Сообщество

- [GitHub Discussions](https://github.com/cloudcastle-apps/di/discussions) — вопросы, идеи, примеры
- [Issues](https://github.com/cloudcastle-apps/di/issues) — баги и задачи

## Документация

- [Wiki — главная](https://github.com/cloudcastle-apps/di/wiki/Home) · [быстрый старт](https://github.com/cloudcastle-apps/di/wiki/Quick-start) · [autowiring](https://github.com/cloudcastle-apps/di/wiki/Autowiring) · [примеры bootstrap](https://github.com/cloudcastle-apps/di/wiki/Bootstrap) · [API](https://github.com/cloudcastle-apps/di/wiki/API-reference)
- Исходники Wiki в каталоге [`wiki/`](wiki/Home) (внутренние ссылки **без** суффикса `.md`)
- [Поддержка](SUPPORT.md) — куда обратиться за помощью
- [Руководство для разработчиков](CONTRIBUTING.md) — окружение, тесты, CI
- [История изменений](CHANGELOG.md) · [Обновление версий](UPGRADING.md)
- API-документация: `composer docs` → каталог `docs/`

## Качество

```bash
composer install
composer ci
```

Пайплайн: линтеры, PHPStan (max), Psalm (L1), PHPMD, Deptrac, Rector, unit/integration/security/load/performance-тесты, покрытие строк ≥95%, Infection MSI ≥95%.

## Лицензия

Распространяется под [лицензией MIT](LICENSE).
