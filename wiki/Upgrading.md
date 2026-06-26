# Обновление версий

Руководство по переходу между версиями **cloudcastle/di**.

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

Major-версия будет описана здесь и в [CHANGELOG](https://github.com/cloudcastle-apps/di/blob/main/CHANGELOG.md).

## Общие рекомендации

1. Прочитайте CHANGELOG для выбранной версии.
2. Запустите тесты проекта после `composer update`.
3. [Issues](https://github.com/cloudcastle-apps/di/issues) · [Discussions Q&A](https://github.com/cloudcastle-apps/di/discussions/categories/q-a)
