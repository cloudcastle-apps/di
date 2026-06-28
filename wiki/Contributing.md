<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 🤝 Участие в разработке

> [← Главная](Home) · [Сравнение](Comparison) · [Quick start](Quick-start)


## Требования

- PHP **8.1–8.5** (CI-матрица)
- Composer 2.x
- расширения: `json`, `mbstring`, `tokenizer`, `xml`, `dom`, `libxml`
- `ext-yaml` — для YAML-конфигурации и полного `composer ci`
- PCOV или Xdebug — для `test:coverage` / локального Infection

## Настройка

```bash
git clone https://github.com/cloudcastle-apps/di.git
cd di
composer install
```

## Структура

```
src/
  Container.php              — основной контейнер
  LazyGhostProxyFactory.php  — lazy ghost/proxy (v1.18, opt-in var-exporter)
  Autowirer.php              — autowiring: конструктор → свойства → методы
  ContainerProfiler.php      — performance profiler (v1.15)
  ServiceObjectPool.php      — memory pool для make() (v1.16)
  ServiceTtlRegistry.php     — smart cache TTL (v1.17)
  Configuration/             — ContainerConfigurator, загрузчики, merger
  Compiler/                  — ContainerCompiler, compiled container (v1.9)
  Contract/                  — ContainerInterface, compiler contracts
tests/Unit/                  — 689 unit-тестов
tests/Integration/           — 11 integration
tests/Security/              — 17 security
tests/Load/                  — 15 load
tests/Performance/           — 12 performance
doc/guide/                   — RST для phpDocumentor
wiki/                        — исходники GitHub Wiki
tools/                       — coverage-check, infection runner, benchmarks
```

## Архитектура (Deptrac)

| Слой | Namespace | Зависит от |
|------|-----------|------------|
| Contract | `CloudCastle\DI\Contract\` | PSR, Exception |
| Core | `CloudCastle\DI\` | Contract, Exception, PSR |
| Exception | `CloudCastle\DI\Exception\` | PSR |
| Tests | `CloudCastle\DI\Tests\` | все слои src |

External: `Symfony\Component\VarExporter\*` — только `LazyGhostProxyFactory` (opt-in lazy ghost).

## Команды

| Команда | Назначение |
|---------|------------|
| `composer check` | lint + analyse + phpcs + phpmd + deptrac + test |
| `composer ci` | полный пайплайн (как GitHub Actions Quality) |
| `composer ci:meta` | normalize, audit, unused |
| `composer ci:linters` | parallel-lint, cs-fixer, phpcs |
| `composer ci:static-analysis` | phpstan, psalm, phpmd, deptrac, rector |
| `composer test:unit` | 689 unit-тестов |
| `composer test:coverage` | ≥95% per-file + project |
| `composer test:mutation` | Infection MSI ≥94% |
| `composer docs:check` | phpDocumentor + `tools/docs-check.php` |

## Pull Request

1. Ветка от актуального `main`.
2. `composer ci` локально — зелёный (PHP 8.1+).
3. PR в `main`: что / зачем / как проверить.

Защита `main`: обязательны checks **PHP 8.1**–**8.5** (workflow **Quality**).

## Wiki

Страницы wiki — каталог `wiki/`. При push в `main` workflow **Publish wiki** синхронизирует их с вкладкой Wiki.

Служебные: `_Sidebar.md`, `_Footer.md`.

**Ссылки между страницами** — без `.md` (например `[Autowiring](Autowiring)`).

## Issues, Milestones

| Milestone | Назначение |
|-----------|------------|
| [v1.18.0](https://github.com/cloudcastle-apps/di/milestone/10) | Lazy ghost proxy (#34) — **выпущен** |
| [Backlog](https://github.com/cloudcastle-apps/di/milestone/2) | Идеи без фиксированного релиза |
| [v2.0](https://github.com/cloudcastle-apps/di/milestone/3) | Breaking changes (scopes #33, policy #17) |

Метки: `feat`, `fix`, `release`, `roadmap`, `area:*`, `good first issue`.

Закреплённый roadmap: [issue #12](https://github.com/cloudcastle-apps/di/issues/12).

## Кодекс и governance

- [CODE_OF_CONDUCT](https://github.com/cloudcastle-apps/di/blob/main/CODE_OF_CONDUCT.md)
- [GOVERNANCE](https://github.com/cloudcastle-apps/di/blob/main/GOVERNANCE.md)
- [SECURITY](https://github.com/cloudcastle-apps/di/blob/main/SECURITY.md)
