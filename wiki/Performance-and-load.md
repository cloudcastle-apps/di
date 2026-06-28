<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 📈 Нагрузочные и performance-тесты

> [← Главная](Home) · [Сравнение](Comparison) · [Quick start](Quick-start)


Регрессионные тесты **масштаба** (load) и **латентности** (performance) гарантируют, что контейнер остаётся быстрым и корректным при росте числа сервисов.

| Каталог | Тестов | Команда | Фокус |
|---------|--------|---------|-------|
| `tests/Load/` | 15 | `composer test:load` | 1000–3000 операций, корректность + пороги времени |
| `tests/Performance/` | 12 | `composer test:performance` | 1000–10000 итераций одной операции |

Входят в `composer ci` и GitHub Actions (PHP 8.1–8.5).

Краткая выжимка — [README → Качество](https://github.com/cloudcastle-apps/di#качество), `doc/guide/load-performance.rst`.

---

## Методология

### Load-тесты

- **Цель:** корректность при «толстом» графе (много `set`, `get`, alias, тегов) и верхняя граница времени на bulk-сценарии.
- **Масштаб:** обычно 1500–2000 сервисов, до 4000 последовательных `get`.
- **Пороги:** `assertLessThan(N секунд)` с запасом для shared CI runners.
- **Не измеряют:** throughput под параллельными запросами (PHP-FPM — один процесс на запрос).

### Performance-тесты

- **Цель:** латентность **одной** операции в цикле (hot path).
- **Метод:** `microtime(true)` до/после цикла; внутри цикла — assertions на корректность.
- **Пороги:** константы в классе (`GET_TIME_BUDGET_SECONDS` и т.д.).
- **Отличие от load:** меньше уникальных id, больше повторений одного hot path.

### Референсные бенчмарки и регрессия в CI

| Команда | Назначение |
|---------|------------|
| `composer benchmark-report` | Markdown + JSON в `var/benchmark/` (p50/p95/p99, ops/sec, memory peak) |
| `composer benchmark-report-json` | Только JSON-отчёт |
| `composer benchmark-check` | Регрессия: **elapsed** и **memory peak** > порога ×1.5 (CI после `test:performance`) |

`tools/benchmark-lib.php` — сценарии runtime/compiled/contextual binding; артефакты CI: `benchmark-reports-php-{8.1..8.5}`.

### Lazy ghost proxy (v1.18.0, #34)

Opt-in Symfony-style proxy для interface — без autoload реализации до первого вызова метода:

```bash
composer require symfony/var-exporter
```

| Метод | Назначение |
|-------|------------|
| `lazyGhost($interface, $serviceId)` | Proxy; `get($serviceId)` при первом вызове метода |

Overhead только при использовании; без `symfony/var-exporter` — `ContainerException`. См. [Prototypes-alias-lazy](Prototypes-alias-lazy).

### Performance Profiler (v1.15.0, #65)

Opt-in замеры `get()` / `make()` / `call()` без overhead в prod:

| Метод | Назначение |
|-------|------------|
| `enableProfiling()` / `disableProfiling()` | Включить/выключить сбор |
| `profileReport($limit)` | top-N медленных операций + агрегаты по типу |
| `resetProfile()` | Очистить накопленные замеры |

### Smart Caching (v1.17.0, #64)

Opt-in TTL для singleton-кэша `get()`:

| Метод | Назначение |
|-------|------------|
| `cacheFor($id, $ttlSeconds)` | TTL для id |
| `cacheTagFor($tag, $ttlSeconds)` | TTL для всех сервисов с тегом |
| `forget($id)` / `forgetTag($tag)` / `forgetAll()` | Явная инвалидация |
| `cacheStats($id)` | TTL, cached, expires_at |

### Memory Pool (v1.16.0, #63)

Opt-in переиспользование экземпляров для `make()`:

| Метод | Назначение |
|-------|------------|
| `enablePooling($id, $maxSize)` | Включить пул для id |
| `releaseToPool($id, $instance)` | Вернуть экземпляр в пул (`PoolableInterface::reset()`) |
| `poolStats($id)` | Свободные экземпляры и лимит |

---

## Load: `ContainerLoadTest` (базовый API)

Файл: `tests/Load/ContainerLoadTest.php`. `SERVICE_COUNT = 2000`.

### 1. `testRegistersAndResolvesManyServices`

| | |
|---|---|
| **Действие** | 2000× `set('service.N', stdClass)` → 2000× `get` |
| **Проверка** | каждый `get` возвращает `stdClass` |
| **Порог времени** | нет (только корректность) |
| **Смысл** | линейная регистрация и разрешение готовых экземпляров |

### 2. `testResolvesManySingletonFactoriesOnce`

| | |
|---|---|
| **Действие** | 2000 фабрик; на каждый id — 2× `get` |
| **Проверка** | фабрика вызвана ровно **2000** раз (не 4000) |
| **Смысл** | singleton-кэш при массовом графе |

### 3. `testCompletesBulkResolutionWithinTimeBudget`

| | |
|---|---|
| **Действие** | 2000 фабрик `bulk.N` → **4000** `get` (циклический id) |
| **Порог** | **< 2.0 с** |
| **Смысл** | bulk resolve не деградирует квадратично |

### 4. `testResolvesManyServicesThroughAliasChains`

| | |
|---|---|
| **Действие** | цепочка alias: `root` → `alias.a` → `alias.b` → `alias.c` (2000 наборов) |
| **Проверка** | `get('alias.c.N')` === `get('root.N')` |
| **Смысл** | разрешение alias под нагрузкой |

### 5. `testDecoratedSingletonFactoriesResolveOnceUnderLoad`

| | |
|---|---|
| **Действие** | 500 сервисов с `decorate()`; 2× `get` каждого |
| **Проверка** | 500 вызовов фабрики и 500 вызовов декоратора |
| **Смысл** | декоратор не срабатывает на каждый `get` из кэша |

---

## Load: `ContainerV13LoadTest` (API v1.3)

Файл: `tests/Load/ContainerV13LoadTest.php`. `SERVICE_COUNT = 1500`.

### 6. `testAddDefinitionsRegistersAndResolvesManyServices`

| | |
|---|---|
| **Действие** | массив 1500 `stdClass` → `addDefinitions()` → 1500× `get` |
| **Смысл** | массовая регистрация v1.3 |

### 7. `testBindManyAliasesToRegisteredIds`

| | |
|---|---|
| **Действие** | 1500× `set` + `bind('abstract.N', 'service.N')` → `get` по abstract |
| **Смысл** | `bind()` на существующий id под нагрузкой |

### 8. `testMakeManyPrototypesFromFactories`

| | |
|---|---|
| **Действие** | 1500 сервисов; на каждый 2× `make` |
| **Проверка** | два разных экземпляра (`assertNotSame`) |
| **Смысл** | прототипы не попадают в singleton-кэш |

### 9. `testCallManyTimesWithExplicitParameters`

| | |
|---|---|
| **Действие** | **3000**× `call(fn (int $n) => $n, ['number' => $i])` |
| **Порог** | **< 3.0 с** |
| **Смысл** | `CallableInvoker` без autowire на объёме |

### 10. `testAfterResolvingInvokesCallbackForEachFirstGet`

| | |
|---|---|
| **Действие** | 1500 hook + 2× `get` на id |
| **Проверка** | callback вызван **1500** раз (не при втором `get` из кэша) |
| **Смысл** | семантика afterResolving под нагрузкой |

### 11. `testManyAliasesToAutowiredClass`

| | |
|---|---|
| **Действие** | autowire `SimpleService`; 1500 alias → FQCN |
| **Проверка** | каждый `get('alias.N')` — `SimpleService` |
| **Смысл** | alias + autowire + singleton |

---

## Load: `ContainerTaggedLoadTest` (теги)

Файл: `tests/Load/ContainerTaggedLoadTest.php`. `TAGGED_COUNT = 1000`.

### 12. `testGetTaggedIdsReturnsManyIdsWithoutResolution`

| | |
|---|---|
| **Действие** | 1000 handler в теге `handlers` |
| **Проверка** | `getTaggedIds` возвращает 1000 id **без** eager `get()` |
| **Смысл** | лёгкий доступ к списку id |

### 13. `testGetTaggedIteratorResolvesManyHandlers`

| | |
|---|---|
| **Действие** | `foreach (getTaggedIterator('handlers'))` |
| **Проверка** | 1000 разрешённых экземпляров |
| **Смысл** | ленивое/последовательное разрешение по тегу |

### 14. `testGetTaggedLocatorHasAndGetMany`

| | |
|---|---|
| **Действие** | `getTaggedLocator` → 1000× `has` + `get` |
| **Смысл** | locator API под нагрузкой |

### 15. `testTaggedBulkOperationsWithinTimeBudget`

| | |
|---|---|
| **Действие** | 1000 фабрик в теге `pipeline` → `getTaggedIds` + iterator с суммой |
| **Порог** | **< 2.5 с** |
| **Смысл** | комбинированный tagged bulk |

---

## Performance: `ContainerPerformanceTest` (базовый API)

Файл: `tests/Performance/ContainerPerformanceTest.php`.

| # | Тест | Итераций | Порог | Операция |
|---|------|----------|-------|----------|
| 1 | `testGetCachedServiceCompletesWithinBudget` | 10 000 | 0.5 с | `get('cached')` — hit кэша |
| 2 | `testHasExistingServiceCompletesWithinBudget` | 10 000 | 0.5 с | `has('cached')` |
| 3 | `testHasDefinitionCompletesWithinBudget` | 10 000 | 0.5 с | `hasDefinition('cached')` |
| 4 | `testSetServiceCompletesWithinBudget` | 5 000 | 0.5 с | `set('dynamic.i', ...)` |

**Интерпретация:** hot path `get`/`has` из кэша — микросекунды на вызов на референсном CPU; порог 0.5 с даёт ~50 µs/итерацию запаса.

---

## Performance: `ContainerV13PerformanceTest`

Файл: `tests/Performance/ContainerV13PerformanceTest.php`.

| # | Тест | Итераций | Порог | Операция |
|---|------|----------|-------|----------|
| 5 | `testCallWithExplicitParametersCompletesWithinBudget` | 10 000 | 0.75 с | `call` + явные параметры |
| 6 | `testMakeUncachedServiceCompletesWithinBudget` | 5 000 | 1.0 с | `make('proto')` каждый раз новый |
| 7 | `testBindAndGetCompletesWithinBudget` | 1 000 | 0.75 с | `bind` + `get` + autowiring on |
| 8 | `testGetTaggedIdsCompletesWithinBudget` | 10 000 | 6.0 с | 200 id в теге, повтор `getTaggedIds` |
| 9 | `testAfterResolvingOnFirstGetCompletesWithinBudget` | 1 000 | 1.0 с | первый `get` с hook |

---

## Performance: `ContainerAutowirePerformanceTest`

Файл: `tests/Performance/ContainerAutowirePerformanceTest.php`.

| # | Тест | Итераций | Порог | Операция |
|---|------|----------|-------|----------|
| 10 | `testCachedAutowireGetCompletesWithinBudget` | 10 000 | 0.75 с | повторный `get(SimpleService::class)` после warm-up |
| 11 | `testColdAutowireGetCompletesWithinBudget` | 500 | 1.5 с | **новый** Container + autowire + `get` каждый раз |
| 12 | `testCallWithAutowireDependencyCompletesWithinBudget` | 2 000 | 1.25 с | `call` с autowire `SimpleService` |

**Cold vs warm:** тест 11 — худший случай (reflection + новый граф); тест 10 — типичный hot path после bootstrap.

---

## Референсный прогон бенчмарков

Среда: **PHP 8.3.31**, UTC **2026-06-27**. Обновление:

```bash
composer benchmark-report
composer benchmark-check    # проверка регрессии (как в CI)
```

| Сценарий | Итераций | Порог (мс) | Факт (мс) | Статус |
|----------|----------|------------|-----------|--------|
| get() из кэша | 10000 | 500 | 86.08 | OK |
| has() зарегистрированного id | 10000 | 500 | 48.62 | OK |
| set() новых сервисов | 5000 | 500 | 16.94 | OK |
| make() прототипов | 5000 | 1000 | 85.22 | OK |
| call() с явными параметрами | 10000 | 750 | 172.98 | OK |
| call() с autowire | 2000 | 1250 | 140.82 | OK |
| bind() + get() | 1000 | 750 | 35.76 | OK |
| getTaggedIds() (200 id) | 10000 | 350 | 18.61 | OK |
| bulk get() 4000 разрешений | 4000 | 2000 | 65.26 | OK |
| холодный autowire get() | 500 | 1500 | 90.88 | OK |

Фактические значения зависят от CPU. В CI: PHPUnit-пороги + **`benchmark-check`** (×1.5).

---

## Как добавить или ужесточить порог

1. Изменить константу `*_TIME_BUDGET_SECONDS` в тестовом классе.
2. Локально: `composer test:load` / `composer test:performance`.
3. Сверить запас: `composer benchmark-report` на слабом железе или в Docker.
4. Порог должен быть **выше** p95 на CI минимум в 2–3 раза, иначе flaky tests.

---

## CI

- `composer ci` — полный пайплайн (unit → … → performance → **benchmark-check** → coverage → mutation).
- `.github/workflows/quality.yml` — матрица PHP 8.1–8.5; CodeQL отдельным workflow.
- Load/performance не требуют PCOV/Xdebug; benchmark-check — после performance.

## Реализовано (performance roadmap)

| | Направление | Релиз |
|---|---|---|
| 🧪 | **Advanced Benchmarks** — p50/p95/p99, ops/sec | v1.14.0 ([#66](https://github.com/cloudcastle-apps/di/issues/66)) |
| 📊 | **Performance Profiler** | v1.15.0 ([#65](https://github.com/cloudcastle-apps/di/issues/65)) |
| ⚡ | **Memory Pool** | v1.16.0 ([#63](https://github.com/cloudcastle-apps/di/issues/63)) |
| 🎯 | **Smart Caching** | v1.17.0 ([#64](https://github.com/cloudcastle-apps/di/issues/64)) |
| 👻 | **Lazy ghost proxy** | v1.18.0 ([#34](https://github.com/cloudcastle-apps/di/issues/34)) |

Обзор — [Home](Home) · [Upgrading](Upgrading).

## См. также

- [Тесты безопасности](Security-tests)
- [Тестирование](Testing) — unit/integration, coverage, mutation
- [Архитектура](Architecture) — потоки resolve и autowiring
