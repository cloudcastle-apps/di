# Обновление версий

Руководство по переходу между версиями **cloudcastle/di**.

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

Major **v2.0** — breaking changes и enterprise-parity (compiled container, contextual binding, scopes). Полный список — в [issue #17](https://github.com/cloudcastle-apps/di/issues/17) и [CHANGELOG.md](CHANGELOG.md) перед релизом.

### Планируемые breaking changes (черновик)

| Область | Изменение | Миграция |
|---------|-----------|----------|
| Минимальная версия PHP | Поднятие до **8.4+** (ориентир) | Обновить `composer.json`, CI и Docker-образы |
| Deprecated API | Удаление устаревших alias/методов, если появятся в 1.x | Заменить на актуальные из UPGRADING 1.x |
| Конфигурация | Возможное ужесточение схемы YAML/JSON/XML (обязательные ключи, типы) | Прогнать `configure()` в тестах, сверить с wiki Configuration |
| Autowiring | Изменение порядка разрешения или opt-in по умолчанию | Явно вызывать `enableAutowiring()` / флаги в config |
| Compiled container (#24) | Опциональный `dump()` → PHP-класс вместо reflection на hot path | Генерировать compiled-контейнер в build-step |
| Contextual binding (#25) | Новый API `when()->needs()->give()` | Перенести «магические» bind из фабрик в declarative config |
| Scopes (#33) | request / transient как first-class | Не полагаться на singleton для request-scoped сервисов |

### Чек-лист перед переходом на 2.0

1. Зафиксировать версию **1.7.x** в production; прочитать release notes 2.0 RC.
2. `composer update cloudcastle/di` на staging; прогнать полный test-suite и smoke-тесты composition root.
3. Проверить: `freeze()` в bootstrap, отсутствие `set()` после freeze, `ContainerRegistry::reset()` в PHPUnit.
4. Заменить удалённые API по таблице в release notes (будет дополнена в RC).
5. При использовании compiled container — добавить шаг сборки в CI/deploy.

### ADR (Architecture Decision Records)

Решения по v2.0 фиксируются в GitHub Discussions / Issues до появления каталога `docs/adr/`:

- **Compiled container** — [#24](https://github.com/cloudcastle-apps/di/issues/24)
- **Contextual binding** — [#25](https://github.com/cloudcastle-apps/di/issues/25)
- **Scopes** — [#33](https://github.com/cloudcastle-apps/di/issues/33)
- **Breaking policy** — [#17](https://github.com/cloudcastle-apps/di/issues/17)

Следите за [Discussions → Ideas](https://github.com/cloudcastle-apps/di/discussions/categories/ideas) и [Releases](https://github.com/cloudcastle-apps/di/releases).

## Общие рекомендации

1. Прочитайте [CHANGELOG.md](CHANGELOG.md) для выбранной версии.
2. Запустите тесты проекта после `composer update`.
3. При проблемах — [Issues](https://github.com/cloudcastle-apps/di/issues) или [Discussions Q&A](https://github.com/cloudcastle-apps/di/discussions/categories/q-a).
