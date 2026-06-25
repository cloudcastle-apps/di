# CloudCastle DI

Лёгкий контейнер внедрения зависимостей для **PHP 8.3+** с поддержкой [PSR-11](https://www.php-fig.org/psr/psr-11/). Одна runtime-зависимость — `psr/container`.

## Установка

```bash
composer require cloudcastle/di
```

Packagist: https://packagist.org/packages/cloudcastle/di

## Возможности

### Регистрация и получение сервисов

- готовые экземпляры и фабрики через `set()`;
- singleton-кэш: фабрика вызывается один раз до следующего `set()`;
- PSR-11: `get()`, `has()`;
- расширенный контракт: `hasDefinition()`.

### Autowiring

- **`enableAutowiring()`** — любой instantiable-класс доступен по FQCN через `get()`;
- **`autowire(FQCN)`** — точечная регистрация без глобального режима;
- **`enableParameterNameAutowiring()`** — id сервиса = имя параметра (`$logger` → `'logger'`);
- **`enablePropertyAutowiring()`** / **`enableMethodAutowiring()`** — typed properties и inject-методы после конструктора;
- PHP attributes **`Inject`** / **`Autowire`** на конструкторе, **свойствах** и **методах**;
- разрешение зависимостей: типы, union, **intersection**, nullable, `ContainerInterface` / PSR-11;
- обнаружение **циклических зависимостей** при autowiring;
- явный `set()` всегда имеет **приоритет** над autowiring.

### Сканирование каталогов

- **`scan($directory, $namespace?)`** — рекурсивный обход `.php`-файлов;
- парсинг `namespace` и `class` без выполнения файла;
- фильтр по префиксу namespace;
- только instantiable-классы; существующие `set()` не перезаписываются.

### Tagged services и декораторы

- **`tag()` / `getTagged()`** — группы сервисов (порядок = порядок `tag()`);
- **`decorate()`** — цепочка обёрток при `get()` (первый декоратор ближе к inner).

### Глобальный реестр

- **`ContainerRegistry`** — singleton-контейнер приложения;
- инициализация в bootstrap через `ContainerRegistry::set()`;
- `reset()` для изоляции тестов.

### Качество

PHPStan max, Psalm L1, покрытие строк ≥95%, Infection MSI ≥95%.

## Минимальный пример

```php
<?php

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerRegistry;

$container = new Container();
$container->enableAutowiring();
$container->scan(__DIR__ . '/App/Services', 'App\\Services\\');

ContainerRegistry::set($container);

$service = ContainerRegistry::get()->get(App\Services\OrderService::class);
```

## Документация

| Страница | Описание |
|----------|----------|
| [Быстрый старт](Quick-start.md) | установка, PSR-11, composition root |
| [Примеры bootstrap](Bootstrap.md) | plain PHP, CLI, unit/integration тесты |
| [Autowiring](Autowiring.md) | reflection, типы параметров, циклы, приоритеты |
| [Сканирование классов](Class-scanning.md) | `scan()`, фильтр namespace, ограничения |
| [Глобальный реестр](Global-registry.md) | `ContainerRegistry`, bootstrap, тесты |
| [Теги и декораторы](Tags-and-decorators.md) | `tag()`, `getTagged()`, `decorate()` |
| [Справочник API](API-reference.md) | все методы и исключения |
| [Фабрики и singleton](Factories-and-singleton.md) | callable, кэш, `null`, циклы в фабриках |
| [Тестирование](Testing.md) | unit/integration, моки, `ContainerRegistry::reset()` |
| [Анти-паттерны](Anti-patterns.md) | service locator, autowiring, глобальный контейнер |
| [Обновление версий](Upgrading.md) | миграция между релизами |
| [Участие в разработке](Contributing.md) | `composer ci`, PR |
| [FAQ](FAQ.md) | частые вопросы |

## Ссылки

- [Репозиторий](https://github.com/cloudcastle-apps/di)
- [Discussions](https://github.com/cloudcastle-apps/di/discussions)
- [Issues](https://github.com/cloudcastle-apps/di/issues)
- [Releases](https://github.com/cloudcastle-apps/di/releases)
- [README в репозитории](https://github.com/cloudcastle-apps/di/blob/main/README.md)

## Лицензия

MIT — см. [LICENSE](https://github.com/cloudcastle-apps/di/blob/main/LICENSE).
