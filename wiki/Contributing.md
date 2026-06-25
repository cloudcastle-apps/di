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
src/                 — код библиотеки
tests/Unit/          — unit-тесты
tests/Integration/   — интеграционные
tests/Security/      — безопасность
tests/Load/          — нагрузка
tests/Performance/   — производительность
doc/guide/           — исходники пользовательской документации
wiki/                — исходники GitHub Wiki (публикуется Actions)
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
| `composer test:coverage` | покрытие (100%) |
| `composer test:mutation` | Infection (MSI 100%) |
| `composer docs` | API-документация в `docs/` |

## Pull Request

1. Ветка от актуального `main`.
2. `composer ci` локально — зелёный.
3. PR в `main` с описанием: что / зачем / как проверить.

Защита `main`: обязательны checks **PHP 8.3**, **PHP 8.4**, **PHP 8.5** (workflow Quality).

## Wiki

Страницы wiki хранятся в каталоге `wiki/` репозитория. При push в `main` workflow **Publish wiki** синхронизирует их с вкладкой Wiki.

## Кодекс и governance

- [CODE_OF_CONDUCT](https://github.com/cloudcastle-apps/di/blob/main/CODE_OF_CONDUCT.md)
- [GOVERNANCE](https://github.com/cloudcastle-apps/di/blob/main/GOVERNANCE.md)
- [SECURITY](https://github.com/cloudcastle-apps/di/blob/main/SECURITY.md)
