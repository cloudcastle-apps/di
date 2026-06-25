# Руководство для разработчиков

Спасибо за интерес к CloudCastle DI. Ниже — минимальный набор шагов для локальной разработки и проверки изменений.

## Требования

- PHP 8.3 или новее
- Composer 2.x
- Расширения: `json`, `mbstring`, `tokenizer`, `xml`
- Для покрытия и мутационных тестов: PCOV или Xdebug (`XDEBUG_MODE=coverage`)

## Настройка окружения

```bash
git clone <url-репозитория> cloudcastle-di
cd cloudcastle-di
composer install
```

## Структура проекта

```
src/           — исходный код библиотеки
tests/Unit/    — unit-тесты
tests/Integration/ — интеграционные сценарии
tests/Security/    — тесты безопасности
tests/Load/        — нагрузочные тесты
tests/Performance/ — тесты производительности
tools/         — вспомогательные скрипты CI
doc/guide/     — исходники пользовательской документации для phpDocumentor
```

## Архитектура (Deptrac)

| Слой | Namespace | Зависит от |
|------|-----------|------------|
| Contract | `CloudCastle\DI\Contract\` | PSR, Exception |
| Core | `CloudCastle\DI\` | Contract, Exception, PSR |
| Exception | `CloudCastle\DI\Exception\` | PSR |
| Tests | `CloudCastle\DI\Tests\` | все слои src |

## Команды разработчика

### Быстрая проверка перед коммитом

```bash
composer check
```

### Полный CI (как на GitHub/GitLab)

```bash
composer ci
```

### Отдельные группы

| Команда | Назначение |
|---------|------------|
| `composer lint` | Синтаксис PHP |
| `composer cs-check` / `composer cs-fix` | PHP CS Fixer |
| `composer phpcs` / `composer phpcbf` | PSR-12 |
| `composer phpstan` | PHPStan level max |
| `composer psalm` | Psalm errorLevel 1, totallyTyped |
| `composer phpmd` | PHP Mess Detector |
| `composer deptrac` | Архитектурные слои |
| `composer rector` / `composer rector-fix` | Rector (dry-run / apply) |
| `composer test:unit` | Unit-тесты |
| `composer test:integration` | Интеграционные тесты |
| `composer test:security` | Тесты безопасности |
| `composer test:load` | Нагрузочные тесты |
| `composer test:performance` | Производительность |
| `composer test:coverage` | Покрытие (ожидается 100% строк) |
| `composer test:mutation` | Infection (MSI 100%) |
| `composer docs` | Генерация API-документации в `docs/` |

## Стиль кода

- PSR-12, strict types во всех файлах
- PHPDoc на русском языке для публичного API
- Имена методов и свойств — camelCase (PSR)
- Перед push рекомендуется `composer ci`

## Pull Request

1. Создайте ветку от `main`
2. Внесите изменения атомарно, с понятным описанием
3. Убедитесь, что `composer ci` проходит локально
4. Опишите в PR: что сделано, зачем, как проверить

## CI

- GitHub Actions: `.github/workflows/quality.yml`
- GitLab CI: `.gitlab-ci.yml`

Оба пайплайна запускают PHP 8.3 и 8.4 с тем же набором проверок, что и `composer ci`.

## Сообщить о проблеме

- Баги и предложения — через Issues репозитория
- Уязвимости — см. [SECURITY.md](SECURITY.md)
