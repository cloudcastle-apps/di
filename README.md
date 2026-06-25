# CloudCastle DI

Лёгкий контейнер внедрения зависимостей для PHP 8.3+ с поддержкой [PSR-11](https://www.php-fig.org/psr/psr-11/).

## Возможности

- Регистрация сервисов как готовых экземпляров или фабрик
- Singleton-поведение: фабрика вызывается один раз, результат кэшируется
- Передача контейнера в фабрику для разрешения зависимостей
- Соответствие PSR-11 (`Psr\Container\ContainerInterface`)
- Расширенный контракт с `set()` и `hasDefinition()`
- Строгая типизация, статический анализ на максимальном уровне, 100% покрытие тестами

## Требования

- PHP ^8.3
- `psr/container` ^2.0

## Установка

```bash
composer require cloudcastle/di
```

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cloudcastle/di.svg)](https://packagist.org/packages/cloudcastle/di)
[![Total Downloads](https://img.shields.io/packagist/dt/cloudcastle/di.svg)](https://packagist.org/packages/cloudcastle/di)
[![GitHub Discussions](https://img.shields.io/github/discussions/cloudcastle-apps/di)](https://github.com/cloudcastle-apps/di/discussions)

## Быстрый старт

```php
<?php

use CloudCastle\DI\Container;

$container = new Container();

// Готовый экземпляр
$container->set('logger', new Psr\Log\NullLogger());

// Фабрика с доступом к контейнеру
$container->set('repository', static fn (Container $c) => new UserRepository($c->get('logger')));

$logger = $container->get('logger');
$repository = $container->get('repository');
```

## API

| Метод | Описание |
|-------|----------|
| `get(string $id): mixed` | Возвращает сервис; бросает `NotFoundException`, если не зарегистрирован |
| `has(string $id): bool` | Проверяет, доступен ли сервис (зарегистрирован или уже создан) |
| `set(string $id, mixed $concrete): void` | Регистрирует экземпляр или фабрику; сбрасывает кэш singleton |
| `hasDefinition(string $id): bool` | Проверяет регистрацию без создания экземпляра |

## Сообщество

- [GitHub Discussions](https://github.com/cloudcastle-apps/di/discussions) — вопросы, идеи, примеры использования (шаблоны Q&A, Ideas, Show and tell)
- [Issues](https://github.com/cloudcastle-apps/di/issues) — баги и задачи на разработку

## Документация

- [Руководство для разработчиков](CONTRIBUTING.md) — настройка окружения, тесты, CI
- [Политика безопасности](SECURITY.md)
- [История изменений](CHANGELOG.md)
- API-документация: `composer docs` → каталог `docs/` (генерируется локально)

## Качество

```bash
composer install
composer ci
```

Пайплайн включает линтеры, PHPStan (max), Psalm (level 1), PHPMD, Deptrac, Rector, unit/integration/security/load/performance-тесты, покрытие 100%, мутационное тестирование (Infection MSI 100%).

## Лицензия

Распространяется под [лицензией MIT](LICENSE).
