<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 🧪 Тестирование

> [← Главная](Home) · [Сравнение](Comparison) · [Quick start](Quick-start)


| Команда | Каталог | Тестов | Назначение |
|---------|---------|--------|------------|
| `composer test:unit` | `tests/Unit/` | 562 | API, autowiring, contextual binding runtime |
| `composer test:integration` | `tests/Integration/` | 8 | Parity runtime vs compiled, multi-service |
| `composer test:security` | `tests/Security/` | 17 | Инъекции, утечки в сообщениях об ошибках |
| `composer test:load` | `tests/Load/` | 15 | Массовые операции (~6000 resolve) |
| `composer test:performance` | `tests/Performance/` | 12 | PHPBench, регрессия hot path |
| `composer test:mutation` | Infection | — | MSI ≥94% по `src/` (PHP 8.3+ в CI) |
| `composer test:coverage` | PHPUnit + PCOV | — | ≥95% строк **и** ≥95% per-file (`tools/coverage-check.php`) |
| `composer benchmark-check` | PHPBench | — | Регрессия производительности (×1.5 порог) |

**Всего:** 614 PHPUnit-тестов (562 + 8 + 17 + 15 + 12; без coverage/mutation/benchmark-check).

Фактические метрики (v1.10): покрытие строк **100%** по `src/`; per-file ≥95%; Infection MSI **≥94%** (`src/Compiler/` вне mutation scope). Mutation-тесты в CI — только PHP 8.3+.

## Быстрый старт

```bash
composer test              # unit + integration + security + load
composer test:unit
composer test:coverage     # порог ≥95% строк и per-file
composer test:mutation     # локально: PHP 8.3+
composer benchmark-check
composer ci                # полный пайплайн как в GitHub Actions
```

## Структура unit-тестов

- `tests/Unit/` — контейнер, autowiring, configuration, loaders, freeze/dump
- `tests/Unit/Configuration/*MutationTest.php` — сценарии для Infection
- `tests/Unit/Compiler/` — `ContainerCompiler`, snapshot builder, generator, planner
- `tests/Unit/Contract/ContextualBindingContractTest.php` — контракты contextual binding (#25)
- `tests/Unit/ContainerContextualBindingTest.php` — runtime when/needs/give (#25)
- `tests/Integration/CompiledContainerIntegrationTest.php` — parity runtime vs compiled
- `tests/Fixtures/` — классы и конфиги для autowire и `ContainerConfigurator`

## CI

GitHub Actions (**Quality**): PHP 8.1–8.5 — linters, static analysis, все test suites, coverage (per-file), mutation (8.3+), benchmark-check, phpDocumentor check.

## Изоляция тестов

- `ContainerRegistry::reset()` в `tearDown` при использовании глобального реестра
- Отдельные экземпляры `Container` в большинстве unit-тестов

## См. также

- [Performance and load](Performance-and-load)
- [Configuration](Configuration) · [Справочник параметров](Configuration-reference)
- [Contributing](Contributing)
