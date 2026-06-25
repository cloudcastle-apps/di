# CloudCastle DI

Лёгкий контейнер внедрения зависимостей для **PHP 8.3+** с поддержкой [PSR-11](https://www.php-fig.org/psr/psr-11/).

## Установка

```bash
composer require cloudcastle/di
```

Packagist: https://packagist.org/packages/cloudcastle/di

## Возможности

- регистрация сервисов как готовых экземпляров или фабрик;
- singleton-поведение: фабрика вызывается один раз, результат кэшируется;
- передача контейнера в фабрику для разрешения зависимостей;
- расширенный контракт с `set()` и `hasDefinition()` поверх PSR-11;
- строгая типизация, PHPStan max, Psalm L1, 100% покрытие и Infection MSI 100%.

## Минимальный пример

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

## Что не входит в scope

CloudCastle DI — **минимальная** основа. Нет autowiring, нет сканирования классов, нет глобального singleton-контейнера. Каждый сервис регистрируется явно через `set()`.

## Документация

| Страница | Описание |
|----------|----------|
| [Быстрый старт](Quick-start) | установка и первые шаги |
| [Справочник API](API-reference) | методы контейнера и исключения |
| [Фабрики и singleton](Factories-and-singleton) | callable, кэш, повторная регистрация |
| [Тестирование](Testing) | unit/integration, подмена зависимостей |
| [Анти-паттерны](Anti-patterns) | service locator, autowiring, безопасность |
| [Обновление версий](Upgrading) | миграция между релизами |
| [Участие в разработке](Contributing) | `composer ci`, PR, архитектура |
| [FAQ](FAQ) | частые вопросы |

## Ссылки

- [Репозиторий](https://github.com/cloudcastle-apps/di)
- [Discussions](https://github.com/cloudcastle-apps/di/discussions) — вопросы и идеи
- [Issues](https://github.com/cloudcastle-apps/di/issues) — баги и задачи
- [Releases](https://github.com/cloudcastle-apps/di/releases)
- [README в репозитории](https://github.com/cloudcastle-apps/di/blob/main/README.md)

## Лицензия

MIT — см. [LICENSE](https://github.com/cloudcastle-apps/di/blob/main/LICENSE).
