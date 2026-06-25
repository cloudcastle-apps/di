# Участие в разработке

## Требования

- PHP 8.3+
- Composer 2.x
- расширения: `json`, `mbstring`, `tokenizer`, `xml`
- PCOV или Xdebug для coverage/mutation

## Настройка

```bash
git clone https://github.com/cloudcastle-apps/di.git
cd di
composer install
```

## Структура

```
src/
  Container.php           — основной контейнер
  Autowirer.php           — autowiring: конструктор → свойства → методы
  MemberResolver.php      — attributes, by-name, типы
  PropertyInjector.php    — injection в свойства
  MethodInjector.php      — inject-методы, setter
  ParameterTypeResolver.php — union, intersection, named types
  AttributeServiceIdReader.php — чтение Inject/Autowire
  ClassScanner.php        — scan каталогов
  ContainerRegistry.php   — глобальный реестр
  Attribute/              — Inject, Autowire
  Contract/               — ContainerInterface
  Exception/              — NotFoundException, ContainerException
tests/Unit/               — unit-тесты
tests/Integration/        — интеграционные
tests/Security/           — безопасность
tests/Load/               — нагрузка
tests/Performance/        — производительность
doc/guide/                — исходники пользовательской документации (RST)
wiki/                     — исходники GitHub Wiki (публикуется Actions)
```

## Архитектура (Deptrac)

| Слой | Namespace | Зависит от |
|------|-----------|------------|
| Contract | `CloudCastle\DI\Contract\` | PSR, Exception |
| Core | `CloudCastle\DI\` | Contract, Exception, PSR |
| Exception | `CloudCastle\DI\Exception\` | PSR |
| Tests | `CloudCastle\DI\Tests\` | все слои src |

## Команды

| Команда | Назначение |
|---------|------------|
| `composer check` | быстрая проверка |
| `composer ci` | полный пайплайн (как в GitHub Actions) |
| `composer test:unit` | unit-тесты |
| `composer test:coverage` | покрытие (≥95% строк) |
| `composer test:mutation` | Infection (MSI ≥95%) |
| `composer docs` | API-документация в `docs/` |

## Pull Request

1. Ветка от актуального `main`.
2. `composer ci` локально — зелёный.
3. PR в `main` с описанием: что / зачем / как проверить.

Защита `main`: обязательны checks **PHP 8.3**, **PHP 8.4**, **PHP 8.5** (workflow Quality).

## Wiki

Страницы wiki хранятся в каталоге `wiki/` репозитория. При push в `main` workflow **Publish wiki** синхронизирует их с вкладкой Wiki.

**Ссылки между страницами Wiki** — относительные с суффиксом `.md` (например `[Autowiring](Autowiring.md)`): так они работают и в репозитории, и на опубликованной Wiki.

Основные страницы:

- [Home.md](Home.md) — обзор
- [Quick-start.md](Quick-start.md) · [Autowiring.md](Autowiring.md) · [API-reference.md](API-reference.md)

## Issues, Milestones и Views

Репозиторий использует **Milestones** и **labels** для roadmap:

| Milestone | Назначение |
|-----------|------------|
| [v1.1.0](https://github.com/cloudcastle-apps/di/milestone/1) | Autowiring, scan, registry, tags, релиз |
| [Backlog](https://github.com/cloudcastle-apps/di/milestone/2) | Идеи без фиксированного релиза |
| [v2.0](https://github.com/cloudcastle-apps/di/milestone/3) | Breaking changes (major) |

Метки: `feat`, `fix`, `release`, `roadmap`, `area:*`, `good first issue`.

**Рекомендуемые Issue Views** (Issues → Views → New view → сохранить поиск):

| View | Фильтр |
|------|--------|
| Open roadmap | `is:issue is:open milestone:v1.1.0` |
| Backlog | `is:issue is:open milestone:Backlog` |
| Good first issue | `is:issue is:open label:"good first issue"` |
| Bugs | `is:issue is:open label:bug` |

Скрипты для maintainers (повторная инициализация): `scripts/github-bootstrap.sh`, `scripts/github-create-issues.sh`.

Закреплённый roadmap: [issue #12](https://github.com/cloudcastle-apps/di/issues/12).

## Кодекс и governance

- [CODE_OF_CONDUCT](https://github.com/cloudcastle-apps/di/blob/main/CODE_OF_CONDUCT.md)
- [GOVERNANCE](https://github.com/cloudcastle-apps/di/blob/main/GOVERNANCE.md)
- [SECURITY](https://github.com/cloudcastle-apps/di/blob/main/SECURITY.md)
