# Тесты безопасности

Каталог: `tests/Security/`. **16 тестов** в двух классах:

| Класс | Тестов | Фокус |
|-------|--------|-------|
| `ContainerSecurityTest` | 6 | id, сообщения, registry, alias |
| `ContainerSecurityResolveTest` | 10 | кэш при сбоях, autowiring, теги |

Команда: `composer test:security` (входит в `composer ci`).

## Зачем нужны

Контейнер — **composition root**: ошибки кэширования, autowiring и тексты исключений могут привести к «битым» singleton, неполной инициализации или утечке путей. Security-тесты фиксируют **безопасное поведение** при сбоях resolve — не полный penetration-тест приложения.

---

## Сводная таблица

| # | Тест | Класс | Риск | Ожидание |
|---|------|-------|------|----------|
| 1 | `testMissingServiceDoesNotRegisterResolvedState` | Security | фантомный `has()` | `has` / `hasDefinition` = false |
| 2 | `testFactoryExceptionDoesNotCachePartialInstance` | Security | кэш после throw в фабрике | фабрика вызывается снова |
| 3 | `testServiceIdentifierIsTreatedAsOpaqueString` | Security | SQL-подобный id | id — непрозрачная строка |
| 4 | `testNotFoundMessageContainsOnlyRequestedIdentifier` | Security | утечка путей | только id в сообщении |
| 5 | `testContainerRegistryExceptionDoesNotExposeInternalPaths` | Security | утечка в registry | фиксированный текст |
| 6 | `testMissingAliasTargetDoesNotRegisterResolvedState` | Security | alias на missing | NotFound, target не в `has()` |
| 7 | `testAfterResolvingExceptionDoesNotCacheSingleton` | Resolve | hook throw + кэш | фабрика и hook ×2 |
| 8 | `testDecoratorExceptionDoesNotCacheSingleton` | Resolve | decorator throw | фабрика ×2 |
| 9 | `testAutowiringDisabledRejectsArbitraryClassName` | Resolve | autowire по FQCN без регистрации | NotFound |
| 10 | `testAbstractClassCannotBeRegisteredViaAutowire` | Resolve | abstract через autowire | ContainerException |
| 11 | `testInterfaceIsNotResolvedViaGlobalAutowiring` | Resolve | interface как сервис | NotFound |
| 12 | `testCyclicDependencyMessageContainsOnlyServiceIdentifier` | Resolve | утечка в цикле | только FQCN в тексте |
| 13 | `testAliasCycleRegistrationIsRejectedWithoutCorruptingPriorAlias` | Resolve | битый alias graph | рабочий alias сохраняется |
| 14 | `testTaggedLocatorNotFoundMessageIsScopedToIdAndTag` | Resolve | утечка в tagged API | id + tag только |
| 15 | `testNullByteInServiceIdentifierIsOpaque` | Resolve | null-byte в id | opaque string |
| 16 | `testSetAfterFailedFactoryAllowsRecovery` | Resolve | recovery после сбоя | `set()` заменяет definition |

---

## `ContainerSecurityTest` — идентификаторы и сообщения

### 1–4. Базовые проверки (как раньше)

- **NotFound** не оставляет следов в `has()` / `hasDefinition()`.
- **Фабрика с throw** — singleton не кэшируется, повторный `get()` снова вызывает фабрику.
- **SQL-подобный id** — `set` / `get` без интерпретации строки.
- **NotFound message** — только `Сервис "<id>" не зарегистрирован.`, без `vendor`.

### 5. `ContainerRegistry` без внутренних путей

До `set()` вызов `ContainerRegistry::get()` бросает `ContainerException` с фиксированным текстом про `ContainerRegistry::set()` — без путей `vendor` / `tests`.

### 6. Alias на несуществующий target

`alias('missing.target', 'nowhere')` → `get('missing.target')` бросает NotFound для **конечного** id `nowhere`; `has('nowhere')` остаётся `false`.

---

## `ContainerSecurityResolveTest` — resolve и autowiring

### 7. `afterResolving` при исключении не кэширует singleton

**Шаги:** фабрика + hook, бросающий `RuntimeException`; дважды `get()`.

**Проверка:** фабрика и hook вызваны по **2** раза.

**Поведение контейнера:** при throw в hook singleton **снимается** из кэша (`Container::resolveService`), чтобы не отдавать частично инициализированный сервис.

### 8. Декоратор при исключении не кэширует singleton

Декоратор бросает исключение **до** записи в `$resolved` — фабрика вызывается при каждом `get()`.

### 9. Autowiring выключен — произвольный FQCN

Без `enableAutowiring()` вызов `get(stdClass::class)` → `NotFoundException`, `has()` = false. Нельзя «достать» класс только по имени.

### 10–11. Abstract и interface

- `autowire(AbstractWorker::class)` → `ContainerException` (не instantiable).
- С глобальным autowiring `get(LoggerInterface::class)` → `NotFound` (интерфейс не class).

### 12. Циклическая зависимость — безопасное сообщение

`get(CircularA::class)` → `ContainerException` с FQCN `CircularA`, без подстрок `vendor` / `tests`.

### 13. Цикл alias откатывается

`alias('entry', 'target')` + попытка `alias('target', 'entry')` → исключение; `get('entry')` по-прежнему работает.

### 14. Tagged locator NotFound

Сообщение: `Сервис "<id>" не найден в теге "<tag>".` — без путей файловой системы.

### 15. Null-byte в id

id с `\0` обрабатывается как обычная строка-ключ (opaque).

### 16. Восстановление после сбоя фабрики

После failed `get()` повторный `set('recoverable', $instance)` и успешный `get()` — без лишних вызовов старой фабрики.

---

## Запуск и CI

```bash
composer test:security
composer ci
```

PHP 8.3–8.5 в `.github/workflows/quality.yml`.

## Рекомендации автору приложения

- Whitelist id из пользовательского ввода.
- Не включайте глобальный autowiring для произвольных FQCN из запроса.
- Не полагайтесь на side effects в `afterResolving` без обработки исключений в bootstrap.

## См. также

- [Тестирование](Testing)
- [Нагрузка и производительность](Performance-and-load)
- [SECURITY.md](https://github.com/cloudcastle-apps/di/blob/main/SECURITY.md)
