# Обновление версий

Руководство по переходу между версиями **cloudcastle/di**.

## 1.15.0 → 1.16.0

### Добавлено (обратно совместимо)

- **Memory Pool** ([#63](https://github.com/cloudcastle-apps/di/issues/63))
- Opt-in pooling для `make()`: `enablePooling($id)`, `releaseToPool($id, $instance)` — без изменений поведения, пока не включено
- Сервисы с `PoolableInterface` сбрасываются через `reset()` перед возвратом в пул

```php
$container->enablePooling(Worker::class, maxSize: 32);

$worker = $container->make(Worker::class);
// ... use ...
$container->releaseToPool(Worker::class, $worker);
```

```bash
composer update cloudcastle/di
```

## 1.14.0 → 1.15.0

### Добавлено (обратно совместимо)

- **Performance Profiler** ([#65](https://github.com/cloudcastle-apps/di/issues/65))
- Opt-in: `enableProfiling()`, `profileReport($limit)`, `resetProfile()` — без overhead, пока не включено

```php
$container->enableProfiling();
// ... bootstrap / requests ...
$report = $container->profileReport(10);
$container->resetProfile();
```

```bash
composer update cloudcastle/di
```

## 1.13.0 → 1.14.0

### Добавлено (обратно совместимо)

- **Advanced Benchmarks** ([#66](https://github.com/cloudcastle-apps/di/issues/66))
- `composer benchmark-report` пишет `var/benchmark/benchmark.md` и `benchmark.json`
- `composer benchmark-report-json` — JSON-отчёт для CI artifacts
- `benchmark-check` проверяет wall time **и** memory peak (×1.5)

```bash
composer update cloudcastle/di
composer benchmark-report
```

## 1.12.0 → 1.13.0

### Добавлено (обратно совместимо)

- **Contextual binding в compiled container** ([#25](https://github.com/cloudcastle-apps/di/issues/25), часть 4)
- `ContainerCompiler` встраивает contextual rules в generated PHP и `contextualGive()` на compiled-контейнере
- `when()` на compiled-контейнере по-прежнему недоступен (immutable)

```bash
composer update cloudcastle/di
```

## 1.11.0 → 1.12.0

### Добавлено (обратно совместимо)

- **Contextual binding в конфигурации** ([#25](https://github.com/cloudcastle-apps/di/issues/25), часть 3): секция `contextual` в PHP/JSON/YAML/XML

```php
return [
    'contextual' => [
        ReportService::class => [
            LoggerInterface::class => 'memory.logger',
        ],
    ],
];
```

```yaml
contextual:
  App\ReportService:
    Psr\Log\LoggerInterface: memory.logger
```

Compiled container — contextual **ещё не** в hot path (часть 4).

```bash
composer update cloudcastle/di
```

## 1.10.0 → 1.11.0

### Добавлено (обратно совместимо)

- **Runtime contextual binding** ([#25](https://github.com/cloudcastle-apps/di/issues/25), часть 2): `Container::when()->needs()->give()`
- Autowiring учитывает правила для класса-потребителя (constructor/property/method)

```php
$container->when(ReportService::class)
    ->needs(LoggerInterface::class)
    ->give('memory.logger');
```

Compiled container — contextual **ещё не** в hot path (часть 4). См. [Contextual binding](https://github.com/cloudcastle-apps/di/wiki/Contextual-binding).

```bash
composer update cloudcastle/di
```

## 1.9.0 → 1.10.0

### Добавлено (обратно совместимо)

- **Контракты contextual binding** ([#25](https://github.com/cloudcastle-apps/di/issues/25), часть 1): `ContextualBinding`, `ContextualBindingRegistryInterface`, fluent `when/needs/give`
- Wiki: [Contextual binding](https://github.com/cloudcastle-apps/di/wiki/Contextual-binding)

Runtime (`Container::when()`) **реализован** в v1.11.0 (часть 2). Config/compiled — части 3–4.

```bash
composer update cloudcastle/di
```

## 1.8.0 → 1.9.0

### Добавлено (обратно совместимо)

- **`ContainerCompiler`** — компиляция замороженного контейнера в PHP-класс без reflection на hot path ([#24](https://github.com/cloudcastle-apps/di/issues/24))
- Классы `CloudCastle\DI\Compiler\*`, контракты `ContainerCompilerInterface`, `CompiledContainerInterface`
- Wiki: [Compiled container](https://github.com/cloudcastle-apps/di/wiki/Compiled-container)

### Изменено

- Покрытие: **100%** line coverage по `src/`; per-file gate ≥95%
- Тесты: 604 PHPUnit

Runtime-контейнер **без изменений** для существующего кода. Compiled — опциональный build-step.

```bash
composer update cloudcastle/di
```

## 1.7.0 → 1.8.0

### Изменения (breaking)

- **Минимальная версия PHP:** `^8.3` → **`^8.1`**
- Поддерживаются PHP **8.1+**; CI matrix: **8.1, 8.2, 8.3, 8.4, 8.5**
- Код адаптирован: убраны `readonly class`, атрибут `#[Override]`, типизированные константы класса (требуют PHP 8.3+)

### Для пользователей на PHP 8.1–8.2

Обновление без изменений в вашем коде — ограничение было только на стороне пакета.

### Для пользователей на PHP &lt; 8.1

Оставайтесь на **1.7.x** или обновите PHP.

```bash
composer require cloudcastle/di:^1.8
```

## 1.6.0 → 1.7.0

### Новые возможности (обратно совместимо)

- **`ConfigurationDirectorySource`**, **`ConfigurationFilesSource`**, **`ConfigurationSourceResolver`** — конфигурация из каталога (flat/recursive) и явного списка файлов
- **`ConfigurationDirectoryScan`** — enum `Flat` / `Recursive`
- **`tools/coverage-check.php`** — per-file coverage ≥95% в CI (помимо общего порога)
- Wiki: [Справочник параметров конфигурации](https://github.com/cloudcastle-apps/di/wiki/Configuration-reference)

### Изменения

- **`ContainerConfigurator`:** `$sources` — `list<string|ConfigurationSource|ConfigurationDirectorySource|ConfigurationFilesSource>`
- CI: расширение `ext-yaml` в setup-php

Код без каталогов/списков в `configure()` **не требует изменений**.

```bash
composer update cloudcastle/di
```

## 1.5.0 → 1.6.0

### Изменения (обратно совместимо)

- **CI / качество:** `composer benchmark-check` и шаг регрессии бенчмарков в GitHub Actions; Infection MSI ≥95% **включая** `src/Configuration/`
- **XmlConfigurationLoader:** секция `autowiring` в распарсенном массиве содержит только флаги со значением `true` (раньше могли попадать `enabled => false`)

Код приложения **не требует изменений**, если вы не полагались на наличие ключей `autowiring.*` со значением `false` в массиве после `ContainerConfigurator`.

### Рекомендации

1. `composer update cloudcastle/di`
2. Для maintainers: `composer benchmark-check` локально после правок hot path
3. См. черновик **v2.0** ниже в этом файле

```bash
composer update cloudcastle/di
```

## 1.4.0 → 1.5.0

### Новые возможности (обратно совместимо)

- **`ContainerConfigurator`** — загрузка конфигурации из PHP / JSON / YAML / XML (необязательно)
- **`registerAttribute(string $attributeClass)`** — пользовательские attributes для autowiring (`ServiceIdAttribute`)
- Секция `register_attributes` в файловой конфигурации

Код без конфигурационных файлов и без custom attributes **не требует изменений**.

### Рекомендации

1. `composer update cloudcastle/di`
2. Для YAML: `pecl install yaml` или пакет с `ext-yaml` в Docker/CI
3. Composition root: `configure()` до `freeze()` — см. [Wiki: Configuration](https://github.com/cloudcastle-apps/di/wiki/Configuration)

```bash
composer update cloudcastle/di
```

## 1.3.1 → 1.4.0

### Новые возможности (обратно совместимо)

- **`freeze()`** — запрет `set` / `autowire` / `alias` и др. после bootstrap; `get` / `make` / `call` работают
- **`getDefinitionIds()`** — список id определений
- **`dump()`** — отладочный снимок (tags, aliases, resolved, флаги autowiring)

Рекомендуется вызывать `freeze()` в конце composition root в production.

```bash
composer update cloudcastle/di
```

## 1.3.0 → 1.3.1

### Исправления (обратно совместимо)

- При исключении в **`afterResolving()`** singleton больше не остаётся в кэше — повторный `get()` снова создаёт сервис и вызывает hook.

Код без `afterResolving()` **не требует изменений**. Рекомендуется обновиться, если hook может бросать исключение.

```bash
composer update cloudcastle/di
```

## 1.2.0 → 1.3.0

### Новые возможности (обратно совместимо)

- **`call($callable, $parameters)`** — autowiring при вызове функций и методов
- **`bind($abstract, $concrete)`** — alias + autowire для классов
- **`addDefinitions(array)`** — массовый `set()`
- **`afterResolving($id, $callback)`** — хуки после создания сервиса
- **`getTaggedIds()`**, **`getTaggedIterator()`**, **`getTaggedLocator()`** — работа с тегами без eager `getTagged()`

Код, использующий только `set()` / `get()`, **не требует изменений**.

### Рекомендации

1. Обновите пакет: `composer update cloudcastle/di`
2. Интерфейсы можно регистрировать одной строкой: `bind(Interface::class, Implementation::class)`
3. Для plugin/handler-списков используйте `getTaggedIterator()` вместо `getTagged()`, если не нужна карта id → instance
4. Для вызова action/controller с autowiring — `call()` вместо ручного `get()` + вызова метода
5. Для пост-инициализации сервисов — `afterResolving()` (не срабатывает при чтении из singleton-кэша)

Подробнее — [Wiki: call(), bind(), afterResolving](https://github.com/cloudcastle-apps/di/wiki/Call-bind-callbacks).

## 1.1.0 → 1.2.0

### Новые возможности (обратно совместимо)

- **`make($id)`** — прототип без singleton-кэша
- **`alias($alias, $targetId)`** — альтернативные id
- **`lazy($serviceId)`** — отложенное создание через `LazyService`
- Расширенный **`ClassScanner`:** несколько классов в файле

Код, использующий только `set()` / `get()`, **не требует изменений**.

### Рекомендации

1. Обновите пакет: `composer update cloudcastle/di`
2. Для DTO и stateful-объектов рассмотрите `make()` вместо обхода singleton через отдельные id
3. Интерфейсы можно привязать к реализации через `alias()` без дублирования `set()`

Подробнее — [Wiki: Прототипы, alias и lazy](https://github.com/cloudcastle-apps/di/wiki/Prototypes-alias-lazy).

## 1.0.3 → 1.1.0

### Новые возможности (обратно совместимо)

- Autowiring: `enableAutowiring()`, `autowire()`, расширенные `get()` / `has()`
- PHP attributes `Inject` / `Autowire` (конструктор, свойства, методы), intersection-типы, autowiring по имени параметра
- Autowiring свойств и методов: `enablePropertyAutowiring()`, `enableMethodAutowiring()`
- Сканирование каталогов: `scan()`
- Tagged services и декораторы: `tag()`, `getTagged()`, `decorate()`
- Глобальный реестр: `ContainerRegistry`

Код, использующий только `set()` / `get()`, **не требует изменений**.

### Рекомендации

1. Обновите пакет: `composer update cloudcastle/di`
2. При использовании `ContainerRegistry` добавьте `ContainerRegistry::reset()` в PHPUnit `tearDown`
3. Перед `scan()` прочитайте Wiki «Autowiring» и «Анти-паттерны»

Подробнее — [Wiki Upgrading](https://github.com/cloudcastle-apps/di/wiki/Upgrading) · [wiki/Upgrading.md](wiki/Upgrading.md).

## 1.0.2 → 1.0.3

Изменений в публичном API нет. Wiki, CI и dev-зависимости:

```bash
composer update cloudcastle/di
```

## 1.0.1 → 1.0.2

Изменений в публичном API нет. Обновление метаданных Packagist и README для discoverability:

```bash
composer update cloudcastle/di
```

## 1.0.0 → 1.0.1

Изменений в публичном API нет. Обновление без правок кода:

```bash
composer update cloudcastle/di
```

В релизе 1.0.1 исправлены метаданные Packagist/GitHub и добавлена документация сообщества; поведение контейнера не менялось.

## 1.x → 2.0 (будущее)

Major **v2.0** — breaking changes и enterprise-parity (**contextual binding**, **scopes**). **Compiled container** реализован в **1.9.0** ([#24](https://github.com/cloudcastle-apps/di/issues/24) закрыт).

### Планируемые breaking changes (черновик)

| Область | Изменение | Миграция |
|---------|-----------|----------|
| Минимальная версия PHP | Поднятие до **8.4+** (ориентир) | Обновить `composer.json`, CI и Docker-образы |
| Deprecated API | Удаление устаревших alias/методов, если появятся в 1.x | Заменить на актуальные из UPGRADING 1.x |
| Конфигурация | Возможное ужесточение схемы YAML/JSON/XML (обязательные ключи, типы) | Прогнать `configure()` в тестах, сверить с wiki Configuration |
| Autowiring | Изменение порядка разрешения или opt-in по умолчанию | Явно вызывать `enableAutowiring()` / флаги в config |
| Contextual binding (#25) | Новый API `when()->needs()->give()` | Перенести «магические» bind из фабрик в declarative config |
| Scopes (#33) | request / transient как first-class | Не полагаться на singleton для request-scoped сервисов |

### Чек-лист перед переходом на 2.0

1. Зафиксировать версию **1.9.x** в production; прочитать release notes 2.0 RC.
2. `composer update cloudcastle/di` на staging; прогнать полный test-suite и smoke-тесты composition root.
3. Проверить: `freeze()` в bootstrap, отсутствие `set()` после freeze, `ContainerRegistry::reset()` в PHPUnit.
4. Заменить удалённые API по таблице в release notes (будет дополнена в RC).
5. При использовании compiled container — шаг `ContainerCompiler::compile()` в CI/deploy (см. wiki Compiled container).

### ADR (Architecture Decision Records)

Решения по v2.0 фиксируются в GitHub Discussions / Issues до появления каталога `docs/adr/`:

- **Compiled container** — реализовано в v1.9.0 ([#24](https://github.com/cloudcastle-apps/di/issues/24))
- **Contextual binding** — [#25](https://github.com/cloudcastle-apps/di/issues/25)
- **Scopes** — [#33](https://github.com/cloudcastle-apps/di/issues/33)
- **Breaking policy** — [#17](https://github.com/cloudcastle-apps/di/issues/17)

**Performance & observability (Backlog):** [#63](https://github.com/cloudcastle-apps/di/issues/63) Memory Pool · [#64](https://github.com/cloudcastle-apps/di/issues/64) Smart Caching · [#65](https://github.com/cloudcastle-apps/di/issues/65) Performance Profiler · [#66](https://github.com/cloudcastle-apps/di/issues/66) Advanced Benchmarks

**Ongoing (1.x):** Bug Fixes · API Improvements · Documentation Updates · More Tests — см. [wiki Home → Roadmap](https://github.com/cloudcastle-apps/di/wiki/Home)

Следите за [Discussions → Ideas](https://github.com/cloudcastle-apps/di/discussions/categories/ideas) и [Releases](https://github.com/cloudcastle-apps/di/releases).

## Общие рекомендации

1. Прочитайте [CHANGELOG.md](CHANGELOG.md) для выбранной версии.
2. Запустите тесты проекта после `composer update`.
3. При проблемах — [Issues](https://github.com/cloudcastle-apps/di/issues) или [Discussions Q&A](https://github.com/cloudcastle-apps/di/discussions/categories/q-a).
