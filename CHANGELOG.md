# История изменений

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
версионирование — [SemVer](https://semver.org/lang/ru/).

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

[1.0.0]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.0
