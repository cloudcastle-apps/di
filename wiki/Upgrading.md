<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# ⬆️ Обновление версий

> [← Главная](Home) · [Сравнение](Comparison) · [Quick start](Quick-start)


Руководство по переходу между версиями **cloudcastle/di**.

## 1.10.0 → 1.11.0

### Добавлено (обратно совместимо)

- **Runtime contextual binding** — `Container::when()->needs()->give()` ([#25](https://github.com/cloudcastle-apps/di/issues/25), часть 2)

```bash
composer update cloudcastle/di
```

## 1.9.0 → 1.10.0

### Добавлено (обратно совместимо)

- **Контракты contextual binding** ([#25](https://github.com/cloudcastle-apps/di/issues/25), часть 1): `ContextualBinding`, `ContextualBindingRegistryInterface`, fluent `when/needs/give`
- Wiki: [Contextual binding](Contextual-binding)

Runtime (`Container::when()`) **ещё не реализован** — поведение контейнера не меняется.

```bash
composer update cloudcastle/di
```

## 1.8.0 → 1.9.0

### Добавлено (обратно совместимо)

- **`ContainerCompiler`** — компиляция замороженного контейнера в PHP-класс без reflection на hot path ([#24](https://github.com/cloudcastle-apps/di/issues/24))
- Классы `Compiler/*`: snapshot builder, PHP generator, constructor planner, parameter resolver
- **`CompiledContainerInterface`**, **`AbstractCompiledContainer`** — runtime compiled-контейнера
- Интеграционные тесты parity runtime vs compiled

### Изменено

- **Покрытие:** 100% line coverage по `src/`; per-file gate ≥95%
- **Тесты:** 604 PHPUnit (unit 552, integration 8, security 17, load 15, performance 12)
- CI: `test:performance` с `XDEBUG_MODE=off`; mutation только на PHP 8.3+

### Рекомендации

1. Для production с большим числом `get()` — [Compiled container](Compiled-container): `freeze()` → `ContainerCompiler::compile()` в build-step.
2. Compiled **не** заменяет runtime-контейнер с фабриками и property/method injection — см. ограничения в wiki.

```bash
composer update cloudcastle/di
```

## 1.7.0 → 1.8.0

### Изменения (breaking)

- **Минимальная версия PHP:** `^8.3` → **`^8.1`**
- CI matrix: PHP **8.1–8.5**
- Код пакета: без `readonly class`, `#[Override]`, typed class constants

### Для пользователей на PHP 8.1+

Обновление без изменений в вашем коде.

### Для PHP &lt; 8.1

Оставайтесь на **1.7.x** или обновите PHP.

```bash
composer require cloudcastle/di:^1.8
```

## 1.6.0 → 1.7.0

### Новые возможности (обратно совместимо)

- Конфигурация из **каталога** и **списка файлов** (`ConfigurationDirectorySource`, `ConfigurationFilesSource`)
- Wiki: [Справочник параметров конфигурации](Configuration-reference)

Код без каталогов в `configure()` **не меняется**.

```bash
composer update cloudcastle/di
```

## 1.5.0 → 1.6.0

### Изменения (обратно совместимо)

- **CI:** `composer benchmark-check` и шаг регрессии бенчмарков в GitHub Actions
- **Infection:** MSI ≥94% по всему `src/`, включая `Configuration/` (PHP 8.3+ для mutation)
- **XmlConfigurationLoader:** в `autowiring` только явные `true`-флаги

Код приложения **не меняется**, если не опираетесь на ключи `autowiring.* === false` после парсинга XML.

```bash
composer update cloudcastle/di
```

## 1.4.0 → 1.5.0

### Новые возможности (обратно совместимо)

- **`ContainerConfigurator`** — конфигурация из PHP / JSON / YAML / XML (опционально)
- **`registerAttribute()`** — пользовательские attributes (`ServiceIdAttribute`)

Существующий bootstrap на чистом PHP API **не требует изменений**.

### Рекомендации

1. `composer update cloudcastle/di`
2. YAML: установите `ext-yaml` в окружении, если нужны `.yaml`/`.yml`
3. Вызов `configure()` — **до** `freeze()` в production

Подробнее — [Configuration](Configuration).

## 1.3.1 → 1.4.0

### Новые возможности (обратно совместимо)

- **`freeze()`** / **`isFrozen()`** — блокировка изменений после bootstrap
- **`getDefinitionIds()`** — список id без resolve
- **`dump()`** — снимок состояния для отладки

См. [Wiki: API-reference](https://github.com/cloudcastle-apps/di/wiki/API-reference).

## 1.3.0 → 1.3.1

### Исправления (обратно совместимо)

- Исключение в **`afterResolving()`** снимает singleton из кэша — повторный `get()` не отдаёт «битый» экземпляр.

Обновление: `composer update cloudcastle/di`. Код без `afterResolving()` менять не нужно.

## 1.2.0 → 1.3.0

### Новые возможности (обратно совместимо)

- **`call()`**, **`bind()`**, **`addDefinitions()`**, **`afterResolving()`**
- **`getTaggedIds()`**, **`getTaggedIterator()`**, **`getTaggedLocator()`**

Существующий код с только `set()` / `get()` **работает без изменений**.

### Рекомендуемые шаги

```bash
composer update cloudcastle/di
```

См. [call(), bind(), afterResolving](Call-bind-callbacks) · [Теги и декораторы](Tags-and-decorators).

### Рекомендации

- `bind()` вместо пары `autowire()` + `alias()` для интерфейсов
- `getTaggedIds()` когда не нужно создавать все сервисы тега сразу
- `afterResolving()` для warmup/аудита после первого `get()`, не при каждом чтении из кэша

## 1.1.0 → 1.2.0

### Новые возможности (обратно совместимо)

- **`make($id)`** — прототип без singleton-кэша
- **`alias($alias, $targetId)`** — альтернативные id
- **`lazy($serviceId)`** — `LazyService` с отложенным `get()`
- **`ClassScanner`:** несколько `class` в файле; парсинг `enum` (без регистрации)

Существующий код с только `set()` / `get()` **работает без изменений**.

### Рекомендуемые шаги

```bash
composer update cloudcastle/di
```

См. [Прототипы, alias и lazy](Prototypes-alias-lazy).

## 1.0.3 → 1.1.0

### Новые возможности (обратно совместимо)

Добавлены без breaking changes:

- **Autowiring:** `enableAutowiring()`, `disableAutowiring()`, `autowire()`, расширенный `get()` / `has()`
- **Attributes:** `CloudCastle\DI\Attribute\Inject`, `Autowire`
- **Intersection-типы** и **autowiring по имени:** `enableParameterNameAutowiring()` (по умолчанию выключен)
- **Autowiring свойств и методов:** `enablePropertyAutowiring()`, `enableMethodAutowiring()`; attributes на property/method — всегда
- **Сканирование:** `scan($directory, $namespace?)`
- **Tagged services и декораторы:** `tag()`, `getTagged()`, `decorate()`
- **Глобальный реестр:** `ContainerRegistry::set()` / `get()` / `has()` / `reset()`
- Классы: `Autowirer`, `MemberResolver`, `PropertyInjector`, `MethodInjector`, `ClassScanner`, `ParameterTypeResolver`, `ClassDependencyResolver`, `IntersectionTypeResolver`, `AttributeServiceIdReader`

Существующий код с только `set()` / `get()` **работает без изменений**.

### Рекомендуемые шаги миграции

1. Обновите пакет:

```bash
composer update cloudcastle/di
```

2. При желании включите autowiring в bootstrap вместо ручного `set()` для каждого класса.

3. Если используете `ContainerRegistry`, вызывайте `ContainerRegistry::reset()` в PHPUnit `tearDown`.

4. Прочитайте [Autowiring](Autowiring) и [Анти-паттерны](Anti-patterns) перед `scan()` всего `src/`.

## 1.0.2 → 1.0.3

Изменений в публичном API нет. Wiki, CI и dev-зависимости.

## 1.0.1 → 1.0.2

Изменений в публичном API нет. Keywords/description на Packagist.

## 1.x → 2.0 (будущее)

Major **v2.0** — breaking changes и enterprise-parity (**contextual binding**, **scopes**). Compiled container **уже в 1.9.0** ([#24](https://github.com/cloudcastle-apps/di/issues/24) закрыт).

### Планируется в v2.0

- **Contextual binding** ([#25](https://github.com/cloudcastle-apps/di/issues/25))
- **Scopes** request / transient ([#33](https://github.com/cloudcastle-apps/di/issues/33))
- Breaking API — [#17](https://github.com/cloudcastle-apps/di/issues/17)

### Performance & observability (Backlog)

| Направление | Issue |
|-------------|-------|
| ⚡ Memory Pool — пул объектов для снижения GC | [#63](https://github.com/cloudcastle-apps/di/issues/63) |
| 🎯 Smart Caching — интеллектуальное кэширование с TTL | [#64](https://github.com/cloudcastle-apps/di/issues/64) |
| 📊 Performance Profiler — opt-in get/make/call | ✅ v1.15.0 |
| 🧪 Advanced Benchmarks — расширенные бенчмарки | ✅ v1.14.0 |

### Ongoing (1.x patch)

- 🐛 **Bug Fixes** — `label:bug`
- 🔧 **API Improvements** — без breaking changes
- 📚 **Documentation Updates** — wiki, UPGRADING, guides
- 🧪 **More Tests** — coverage, load, mutation

Major-версия будет описана здесь и в [CHANGELOG](https://github.com/cloudcastle-apps/di/blob/main/CHANGELOG.md).

## Общие рекомендации

1. Прочитайте CHANGELOG для выбранной версии.
2. Запустите тесты проекта после `composer update`.
3. [Issues](https://github.com/cloudcastle-apps/di/issues) · [Discussions Q&A](https://github.com/cloudcastle-apps/di/discussions/categories/q-a)
