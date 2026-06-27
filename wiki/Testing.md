<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 🧪 Тестирование

> [← Главная](Home) · [Сравнение](Comparison) · [Quick start](Quick-start)


| Команда | Каталог | Тестов | Назначение |
|---------|---------|--------|------------|
| `composer test:unit` | `tests/Unit/` | 463 | API, autowiring, configuration, freeze, compiler contracts |
| `composer test:integration` | `tests/Integration/` | 5 | Сценарии с несколькими сервисами |
| `composer test:security` | `tests/Security/` | 17 | Инъекции, утечки в сообщениях об ошибках |
| `composer test:load` | `tests/Load/` | 15 | Массовые операции (~6000 resolve) |
| `composer test:performance` | `tests/Performance/` | 12 | PHPBench, регрессия hot path |
| `composer test:mutation` | Infection | — | MSI ≥94% по `src/` (PHP 8.3+ в CI) |
| `composer test:coverage` | PHPUnit + PCOV | — | ≥95% строк **и** ≥95% per-file (`tools/coverage-check.php`) |
| `composer benchmark-check` | PHPBench | — | Регрессия производительности (×1.5 порог) |

**Всего:** 512 PHPUnit-тестов (без coverage/mutation/benchmark-check).

Фактические метрики (v1.8): покрытие строк **~98%**; per-file ≥95%; Infection MSI **≥94%** по всему `src/` (включая `src/Configuration/`). Mutation-тесты в CI выполняются только на PHP 8.3+ (требование Infection 0.33).

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
- `tests/Unit/Compiler/` — контракты compiled container (v2, #24)
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
