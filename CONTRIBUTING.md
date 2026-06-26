# Руководство для разработчиков

Спасибо за интерес к CloudCastle DI. Участвуя в проекте, вы соглашаетесь с [кодексом поведения](CODE_OF_CONDUCT.md).

Ниже — минимальный набор шагов для локальной разработки и проверки изменений.

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
| `composer test:load` | Нагрузочные тесты (15) |
| `composer test:performance` | Производительность (12) |
| `composer benchmark-report` | Markdown-отчёт фактических времён |
| `composer test:coverage` | Покрытие (ожидается ≥95% строк) |
| `composer test:mutation` | Infection (MSI ≥95%) |
| `composer docs` | Генерация API-документации в `docs/` |

## Стиль кода

- PSR-12, strict types во всех файлах
- PHPDoc на русском языке для публичного API
- Имена методов и свойств — camelCase (PSR)
- Перед push рекомендуется `composer ci`

## GitHub: Issues и Wiki

- **Wiki** — исходники в `wiki/`, синхронизация через workflow `Publish wiki` при push в `main`. Онлайн: https://github.com/cloudcastle-apps/di/wiki/Home
- **Milestones** — v1.1.0, Backlog, v2.0 (см. [Milestones](https://github.com/cloudcastle-apps/di/milestones))
- **Labels** — `feat`, `fix`, `area:*`, `roadmap`, `release`; инициализация: `scripts/github-bootstrap.sh`
- **Issue Views** — сохранённые поиски (см. [wiki/Contributing.md](wiki/Contributing.md) § Issues, Milestones и Views)

Перед багом или фичей проверьте [открытые Issues](https://github.com/cloudcastle-apps/di/issues) и [Discussions](https://github.com/cloudcastle-apps/di/discussions).

## Pull Request

1. Создайте ветку от `main`
2. Внесите изменения атомарно, с понятным описанием
3. Убедитесь, что `composer ci` проходит локально
4. Опишите в PR: что сделано, зачем, как проверить (шаблон подставится автоматически)

Подробнее о процессе: [GOVERNANCE.md](GOVERNANCE.md).

## Брендинг и логотип

Файлы в каталоге `assets/`:

| Файл | Назначение |
|------|------------|
| `logo.svg` | Основной векторный логотип (README, Wiki) |
| `logo.png` | Растровая копия 512×512 |
| `social-preview.png` | Баннер 1280×640 для GitHub Social preview |

**Social preview** (карточка репозитория при шаринге ссылки): [Settings → Social preview](https://github.com/cloudcastle-apps/di/settings) → Edit → загрузить `.github/social-preview.png` или `assets/social-preview.png`. Публичного API для загрузки нет; локально — `node tools/upload-social-preview.mjs` (Playwright + `GH_TOKEN`).

## CI

- GitHub Actions: `.github/workflows/quality.yml`, `.github/workflows/packagist.yml`
- GitLab CI: `.gitlab-ci.yml`
- Dependabot: `.github/dependabot.yml` (Composer dev, GitHub Actions)
- CODEOWNERS: `.github/CODEOWNERS`

Оба пайплайна запускают PHP 8.3 и 8.4 с тем же набором проверок, что и `composer ci`.

## Сообщить о проблеме

См. также [SUPPORT.md](SUPPORT.md).

| Куда | Когда |
|------|--------|
| [Discussions](https://github.com/cloudcastle-apps/di/discussions) | Вопросы по использованию, идеи API, обмен опытом — выберите шаблон (Q&A, Ideas, …) |
| [Issues](https://github.com/cloudcastle-apps/di/issues) | Подтверждённые баги, конкретные задачи на PR |
| [SECURITY.md](SECURITY.md) | Уязвимости (не публикуйте детали в Issues/Discussions) |
