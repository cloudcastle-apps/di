# Управление проектом

CloudCastle DI — open-source библиотека под лицензией MIT. Документ описывает роли, процесс принятия решений и правила релизов.

## Maintainers

| Роль | Ответственность |
|------|-----------------|
| **Maintainers** org [cloudcastle-apps](https://github.com/cloudcastle-apps) | Ревью PR, релизы, политики безопасности, приоритет roadmap |
| **Contributors** | PR, Issues, Discussions по [CONTRIBUTING.md](CONTRIBUTING.md) |

Список maintainers виден в настройках репозитория, в [CODEOWNERS](.github/CODEOWNERS) и в коммитах/релизах GitHub.

## Ветки и Pull Request

- **Защищённая ветка:** `main` — единственная цель merge.
- **Feature/fix-ветки** — от актуального `main`, атомарные PR с зелёным CI.
- **Ревью:** автоназначение через [CODEOWNERS](.github/CODEOWNERS); merge после одобрения maintainer и прохождения `composer ci`.
- **Шаблон PR:** [.github/PULL_REQUEST_TEMPLATE.md](.github/PULL_REQUEST_TEMPLATE.md).

## Принятие решений

1. **Patch/minor** — через Pull Request: обсуждение в PR, зелёный CI, merge maintainer'ом после ревью.
2. **Breaking changes (major)** — сначала обсуждение в [Discussions → Ideas](https://github.com/cloudcastle-apps/di/discussions/categories/ideas) или Issue; затем PR с миграционными заметками в [CHANGELOG.md](CHANGELOG.md) и [UPGRADING.md](UPGRADING.md).
3. **Безопасность** — через [Security Advisories](https://github.com/cloudcastle-apps/di/security/advisories); публичное раскрытие после фикса ([SECURITY.md](SECURITY.md)).
4. **Споры в сообществе** — [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## Релизы

- Версионирование: [SemVer](https://semver.org/lang/ru/).
- История: [CHANGELOG.md](CHANGELOG.md).
- Теги `v*` в GitHub → автоматическое обновление [Packagist](https://packagist.org/packages/cloudcastle/di) (workflow `.github/workflows/packagist.yml`).
- Цитирование пакета: [CITATION.cff](CITATION.cff) (версия обновляется при релизе).

## Документация и сайты

| Ресурс | Где живёт |
|--------|-----------|
| Wiki | `wiki/` в репозитории → [GitHub Wiki](https://github.com/cloudcastle-apps/di/wiki) (workflow `publish-wiki.yml`) |
| API docs (phpDocumentor) | `composer docs` → каталог `docs/` (локально / CI) |
| GitHub Pages | опционально из `docs/`; см. [CNAME](CNAME), [docs/CNAME](docs/CNAME) и `docs/.nojekyll` |
| Профиль организации | [profile/README.md](profile/README.md) → синхронизация в [cloudcastle-apps/.github](https://github.com/cloudcastle-apps/.github) |

## Изменение governance

Предложения — через Issue или Discussion; финальное решение за maintainers org `cloudcastle-apps`.
