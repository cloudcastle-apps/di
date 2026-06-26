# Обновление версий

Руководство по переходу между версиями **cloudcastle/di**.

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

Major-версия будет описана здесь и в [CHANGELOG.md](CHANGELOG.md) до выхода релиза.

Планируется заранее:

- описать breaking changes;
- указать минимальную версию PHP;
- привести примеры миграции типичных сценариев.

Следите за [Discussions → Ideas](https://github.com/cloudcastle-apps/di/discussions/categories/ideas) и [Releases](https://github.com/cloudcastle-apps/di/releases).

## Общие рекомендации

1. Прочитайте [CHANGELOG.md](CHANGELOG.md) для выбранной версии.
2. Запустите тесты проекта после `composer update`.
3. При проблемах — [Issues](https://github.com/cloudcastle-apps/di/issues) или [Discussions Q&A](https://github.com/cloudcastle-apps/di/discussions/categories/q-a).
