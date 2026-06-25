# История изменений

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
версионирование — [SemVer](https://semver.org/lang/ru/).

## [1.0.2] — 2026-06-25

### Добавлено

- Расширенные `keywords` и двуязычное `description` в `composer.json` для поиска на Packagist
- README: English lead, таблица сравнения с PHP-DI / Symfony / Pimple, badges CI и coverage
- GitHub topics репозитория (`php`, `dependency-injection`, `psr-11`, …)
- CODEOWNERS, Dependabot, `UPGRADING.md`, user guide в `doc/guide/`

## [1.0.1] — 2026-06-25

### Исправлено

- Метаданные пакета: `homepage` на Packagist, `support.source` на репозиторий `cloudcastle-apps/di`
- `FUNDING.yml` без блока `github` до одобрения GitHub Sponsors (кнопка Sponsor без ошибки парсинга)

### Добавлено

- Community health files: `CODE_OF_CONDUCT.md`, `SUPPORT.md`, `GOVERNANCE.md`, `CITATION.cff`
- Шаблоны GitHub Discussions, Issues и Pull Request
- Автообновление Packagist через GitHub Actions
- Расширенные тесты: граничные случаи, цепочки зависимостей, performance `hasDefinition`

## [1.0.0] — 2026-06-25

### Добавлено

- Реализация DI-контейнера `CloudCastle\DI\Container`
- Контракт `ContainerInterface`, расширяющий PSR-11
- Исключения `NotFoundException`, `ContainerException`
- Регистрация сервисов через `set()` с поддержкой фабрик и singleton-кэша
- Набор тестов: unit, integration, security, load, performance
- Мутационное тестирование (Infection, MSI 100%)
- CI для GitHub Actions и GitLab CI
- Инструменты качества: PHPStan max, Psalm level 1, PHPCS, PHPMD, Deptrac, Rector

[1.0.2]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.2
[1.0.1]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.1
[1.0.0]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.0
