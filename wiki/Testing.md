<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 🧪 Тестирование

> [← Главная](Home) · [Сравнение](Comparison) · [Quick start](Quick-start)


| Команда | Каталог | Тестов | Назначение |
|---------|---------|--------|------------|
| `composer test:unit` | `tests/Unit/` | 689 | API, autowiring, contextual binding, profiler, pool, smart cache, lazy ghost |
| `composer test:integration` | `tests/Integration/` | 11 | Parity runtime vs compiled, multi-service |
| `composer test:security` | `tests/Security/` | 17 | Инъекции, утечки в сообщениях об ошибках |
| `composer test:load` | `tests/Load/` | 15 | Массовые операции (~6000 resolve) |
| `composer test:performance` | `tests/Performance/` | 12 | PHPBench, регрессия hot path |
| `composer test:mutation` | Infection | — | MSI **100%**, Covered MSI **100%** (`src/`, без `Compiler/`) |
| `composer test:coverage` | PHPUnit + PCOV | 731* | ≥95% строк **и** ≥95% per-file (`tools/coverage-check.php`) |
| `composer benchmark-check` | PHPBench | — | Регрессия производительности (×1.5 порог) |

\* Coverage/mutation suites: unit + integration + security + load (без performance).

**Всего:** 744 PHPUnit-теста (689 + 11 + 17 + 15 + 12).

## Метрики качества (v1.18)

| Метрика | Порог CI | Фактически (типично) |
|---------|----------|----------------------|
| Line coverage | ≥95% проект + **каждый файл** | ~99.8% |
| Infection MSI | **100%** | 100% |
| Infection Covered MSI | **100%** | 100% |
| PHPStan | level **max** | 0 errors |
| Psalm | strict + plugins | 0 errors |
| Deptrac | fail on uncovered | 0 violations |
| Rector | dry-run | 0 diffs |

`src/Compiler/` исключён из mutation scope (`infection.json.dist`).

## Быстрый старт

```bash
composer test              # unit + integration + security + load
composer test:unit
composer test:coverage     # порог ≥95% строк и per-file
composer test:mutation     # Infection (~2–3 мин)
composer benchmark-check
composer ci                # полный пайплайн как в GitHub Actions
```

## Статический анализ и линтеры

| Команда | Инструмент |
|---------|------------|
| `composer lint` | parallel-lint |
| `composer cs-check` | PHP CS Fixer |
| `composer phpcs` | PHPCS (PSR-12) |
| `composer phpstan` | PHPStan max |
| `composer psalm` | Psalm 6 |
| `composer phpmd` | PHPMD |
| `composer deptrac` | Deptrac |
| `composer rector` | Rector (dry-run) |
| `composer ci:meta` | composer-normalize, audit, unused |

## Структура unit-тестов

- `tests/Unit/` — контейнер, autowiring, configuration, loaders, freeze/dump
- `tests/Unit/LazyGhost*` — lazy ghost proxy (#34)
- `tests/Unit/Configuration/*MutationTest.php` — сценарии для Infection
- `tests/Unit/Compiler/` — `ContainerCompiler`, snapshot builder, generator, planner
- `tests/Unit/Contract/ContextualBindingContractTest.php` — контракты contextual binding (#25)
- `tests/Unit/ContainerContextualBindingTest.php` — runtime when/needs/give (#25)
- `tests/Integration/CompiledContainerIntegrationTest.php` — parity runtime vs compiled
- `tests/Fixtures/` — классы и конфиги для autowire и `ContainerConfigurator`

## CI

GitHub Actions (**Quality**): матрица **PHP 8.1–8.5** — meta, linters, static analysis, все test suites, coverage (per-file), **mutation на каждой версии PHP**, benchmark-check, phpDocumentor (`docs:check`). CodeQL — отдельный workflow.

## Изоляция тестов

- `ContainerRegistry::reset()` в `tearDown` при использовании глобального реестра
- Отдельные экземпляры `Container` в большинстве unit-тестов
- `failOnSkipped="true"` в `phpunit.coverage.xml.dist` — пропуски в coverage-run недопустимы

## См. также

- [Performance and load](Performance-and-load)
- [Security-tests](Security-tests)
- [Configuration](Configuration) · [Справочник параметров](Configuration-reference)
- [Contributing](Contributing)
