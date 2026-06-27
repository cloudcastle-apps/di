<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 🧪 Тестирование

> [← Главная](Home) · [Сравнение](Comparison) · [Quick start](Quick-start)


| Команда | Каталог | Тестов | Назначение |
|---------|---------|--------|------------|
| `composer test:unit` | `tests/Unit/` | 457 | API, autowiring, configuration, freeze, mutation-сценарии |
| `composer test:integration` | `tests/Integration/` | 5 | Сценарии с несколькими сервисами |
| `composer test:security` | `tests/Security/` | 17 | Инъекции, утечки в сообщениях об ошибках |
| `composer test:load` | `tests/Load/` | 15 | Массовые операции (~6000 resolve) |
| `composer test:performance` | `tests/Performance/` | 12 | PHPBench, регрессия hot path |
| `composer test:mutation` | Infection | — | MSI ≥95% по `src/` |
| `composer test:coverage` | PHPUnit + PCOV | — | ≥95% строк **и** ≥95% per-file (`tools/coverage-check.php`) |
| `composer benchmark-check` | PHPBench | — | Регрессия производительности (×1.5 порог) |

**Всего:** 506 PHPUnit-тестов (без coverage/mutation/benchmark-check).

Фактические метрики (v1.7): покрытие строк **~98%**; per-file ≥95%; Infection MSI **≥95%** по всему `src/` (включая `src/Configuration/`).

## Быстрый старт

```bash
composer test              # unit + integration + security + load
composer test:unit
composer test:coverage     # порог ≥95% строк и per-file
composer test:mutation
composer benchmark-check
```

## Структура unit-тестов

- `tests/Unit/` — контейнер, autowiring, configuration, loaders, freeze/dump
- `tests/Unit/Configuration/*MutationTest.php` — сценарии для Infection
- `tests/Fixtures/` — классы и конфиги для autowire и `ContainerConfigurator`

## CI

GitHub Actions (**Quality**): PHP 8.1–8.5 — linters, static analysis, все test suites, coverage (per-file), mutation, benchmark-check, phpDocumentor check.

## Изоляция тестов

- `ContainerRegistry::reset()` в `tearDown` при использовании глобального реестра
- Отдельные экземпляры `Container` в большинстве unit-тестов

## См. также

- [Performance and load](Performance-and-load)
- [Configuration](Configuration) · [Справочник параметров](Configuration-reference)
- [Contributing](Contributing)
