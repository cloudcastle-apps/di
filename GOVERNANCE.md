# Управление проектом

CloudCastle DI — open-source библиотека под лицензией MIT. Документ описывает, как принимаются решения и кто за что отвечает.

## Maintainers

| Роль | Ответственность |
|------|-----------------|
| **Maintainers** org [cloudcastle-apps](https://github.com/cloudcastle-apps) | Ревью PR, релизы, политики безопасности, приоритет roadmap |
| **Contributors** | PR, Issues, Discussions по [CONTRIBUTING.md](CONTRIBUTING.md) |

Список maintainers виден в настройках репозитория и в коммитах/релизах GitHub.

## Принятие решений

1. **Patch/minor** — через Pull Request: обсуждение в PR, зелёный CI, merge maintainer'ом после ревью.
2. **Breaking changes (major)** — сначала обсуждение в [Discussions → Ideas](https://github.com/cloudcastle-apps/di/discussions/categories/ideas) или Issue; затем PR с миграционными заметками в CHANGELOG.
3. **Безопасность** — через [Security Advisories](https://github.com/cloudcastle-apps/di/security/advisories); публичное раскрытие после фикса.
4. **Споры в сообществе** — [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## Релизы

- Версионирование: [SemVer](https://semver.org/lang/ru/).
- История: [CHANGELOG.md](CHANGELOG.md).
- Теги в GitHub → автоматическое обновление [Packagist](https://packagist.org/packages/cloudcastle/di).

## Изменение governance

Предложения — через Issue или Discussion; финальное решение за maintainers org `cloudcastle-apps`.
