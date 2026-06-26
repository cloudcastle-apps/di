# Нагрузка и производительность

Регрессионные **нагрузочные** (`tests/Load/`) и **performance** (`tests/Performance/`) тесты проверяют, что контейнер остаётся быстрым при масштабировании регистраций и разрешений.

Пороги зашиты в PHPUnit (`assertLessThan`) — CI падает при деградации. Фактические времена на референсной машине — в таблице ниже (обновляется командой `composer benchmark-report`).

## Наборы тестов (сводка)

| Набор | Каталог | Тестов | Назначение |
|-------|---------|--------|------------|
| unit | `tests/Unit/` | 208 | Поведение API, autowiring, v1.2/v1.3 |
| integration | `tests/Integration/` | 5 | Графы зависимостей, изоляция контейнеров |
| security | `tests/Security/` | 4 | Безопасность регистрации и resolve |
| **load** | `tests/Load/` | **15** | Массовые регистрации и разрешения |
| **performance** | `tests/Performance/` | **12** | Латентность отдельных операций |
| coverage + mutation | — | — | ≥95% строк, Infection MSI ≥95% |

**Всего PHPUnit (без coverage/mutation):** 244 теста.

Команды:

```bash
composer test:load
composer test:performance
composer benchmark-report   # markdown-таблица фактических времён
composer ci                 # полный пайплайн включая load/performance
```

## Нагрузочные сценарии (`tests/Load/`)

| Класс / тест | Масштаб | Порог / критерий |
|--------------|---------|------------------|
| **ContainerLoadTest** | | |
| `testRegistersAndResolvesManyServices` | 2000 `set` + `get` | корректность |
| `testResolvesManySingletonFactoriesOnce` | 2000 фабрик × 2 `get` | 2000 вызовов фабрики |
| `testCompletesBulkResolutionWithinTimeBudget` | 4000 `get` | **< 2 с** |
| `testResolvesManyServicesThroughAliasChains` | 2000 × цепочка alias (3 звена) | корректность |
| `testDecoratedSingletonFactoriesResolveOnceUnderLoad` | 500 decorate + 2× `get` | 500 фабрик и декораторов |
| **ContainerV13LoadTest** | | |
| `testAddDefinitionsRegistersAndResolvesManyServices` | 1500 `addDefinitions` | корректность |
| `testBindManyAliasesToRegisteredIds` | 1500 `bind` + `get` | корректность |
| `testMakeManyPrototypesFromFactories` | 1500 × 2 `make` | разные экземпляры |
| `testCallManyTimesWithExplicitParameters` | 3000 `call` | **< 3 с** |
| `testAfterResolvingInvokesCallbackForEachFirstGet` | 1500 hook + 2× `get` | 1500 callback |
| `testManyAliasesToAutowiredClass` | 1500 alias → FQCN | singleton autowire |
| **ContainerTaggedLoadTest** | | |
| `testGetTaggedIdsReturnsManyIdsWithoutResolution` | 1000 id | без `get()` |
| `testGetTaggedIteratorResolvesManyHandlers` | 1000 итераций | корректность |
| `testGetTaggedLocatorHasAndGetMany` | 1000 `has` + `get` | корректность |
| `testTaggedBulkOperationsWithinTimeBudget` | 1000 id + iterator | **< 2.5 с** |

## Performance-сценарии (`tests/Performance/`)

| Класс / тест | Итераций | Порог времени |
|--------------|----------|---------------|
| **ContainerPerformanceTest** | | |
| `testGetCachedServiceCompletesWithinBudget` | 10 000 `get` | **< 0.5 с** |
| `testHasExistingServiceCompletesWithinBudget` | 10 000 `has` | **< 0.5 с** |
| `testHasDefinitionCompletesWithinBudget` | 10 000 `hasDefinition` | **< 0.5 с** |
| `testSetServiceCompletesWithinBudget` | 5 000 `set` | **< 0.5 с** |
| **ContainerV13PerformanceTest** | | |
| `testCallWithExplicitParametersCompletesWithinBudget` | 10 000 `call` | **< 0.75 с** |
| `testMakeUncachedServiceCompletesWithinBudget` | 5 000 `make` | **< 1.0 с** |
| `testBindAndGetCompletesWithinBudget` | 1 000 `bind`+`get` | **< 0.75 с** |
| `testGetTaggedIdsCompletesWithinBudget` | 10 000 × 200 id | **< 0.35 с** |
| `testAfterResolvingOnFirstGetCompletesWithinBudget` | 1 000 первых `get` | **< 1.0 с** |
| **ContainerAutowirePerformanceTest** | | |
| `testCachedAutowireGetCompletesWithinBudget` | 10 000 `get` FQCN | **< 0.75 с** |
| `testColdAutowireGetCompletesWithinBudget` | 500 новых контейнеров | **< 1.5 с** |
| `testCallWithAutowireDependencyCompletesWithinBudget` | 2 000 `call`+autowire | **< 1.25 с** |

Пороги рассчитаны с запасом для CI (GitHub Actions, GitLab) на PHP 8.3–8.5.

## Референсный прогон бенчмарков

Среда: **PHP 8.3.31**, UTC **2026-06-26**. Обновление: `composer benchmark-report`.

| Сценарий | Итераций | Порог (мс) | Факт (мс) | Статус |
|----------|----------|------------|-----------|--------|
| get() из кэша | 10000 | 500 | 86.98 | OK |
| has() зарегистрированного id | 10000 | 500 | 48.58 | OK |
| set() новых сервисов | 5000 | 500 | 9.65 | OK |
| make() прототипов | 5000 | 1000 | 87.01 | OK |
| call() с явными параметрами | 10000 | 750 | 171.92 | OK |
| call() с autowire | 2000 | 1250 | 119.69 | OK |
| bind() + get() | 1000 | 750 | 29.39 | OK |
| getTaggedIds() (200 id) | 10000 | 350 | 17.02 | OK |
| bulk get() 4000 разрешений | 4000 | 2000 | 57.86 | OK |
| холодный autowire get() | 500 | 1500 | 85.22 | OK |

Фактические значения зависят от CPU и нагрузки; в CI проверяются только пороги PHPUnit.

## CI

Load и performance входят в `composer ci` и workflow `.github/workflows/quality.yml` (PHP 8.3, 8.4, 8.5).

## См. также

- [Тестирование](Testing) — unit/integration, покрытие, мутации
- [Участие в разработке](Contributing) — команды разработчика
- [Архитектура](Architecture) — схемы resolve и autowiring
