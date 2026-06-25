# Быстрый старт

## Требования

- PHP ^8.3
- `psr/container` ^2.0 (подтягивается автоматически)

## Установка

```bash
composer require cloudcastle/di
```

## Минимальный пример

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

## Идентификаторы сервисов

Идентификаторы — произвольные строки (`'logger'`, `'config.timezone'`, `'app.mailer'`). Контейнер **не** резолвит классы по FQCN автоматически.

## PSR-11

`CloudCastle\DI\Container` реализует:

- `Psr\Container\ContainerInterface` — `get()`, `has()`;
- `CloudCastle\DI\Contract\ContainerInterface` — дополнительно `set()`, `hasDefinition()`.

Проверка наличия сервиса:

```php
if ($container->has('logger')) {
    $logger = $container->get('logger');
}
```

`hasDefinition()` проверяет регистрацию **без** создания экземпляра (удобно для фабрик):

```php
if ($container->hasDefinition('repository')) {
    // определение есть, но get() ещё не вызывался
}
```

## Composition root

Собирайте граф зависимостей в одной точке входа приложения (bootstrap), а в домен передавайте готовые объекты через конструктор. Подробнее — [Анти-паттерны](Anti-patterns).

## Дальше

- [Фабрики и singleton](Factories-and-singleton)
- [Справочник API](API-reference)
