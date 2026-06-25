#!/usr/bin/env bash
# Стартовые issues для cloudcastle-apps/di (roadmap + backlog).
set -euo pipefail

REPO="${GITHUB_REPOSITORY:-cloudcastle-apps/di}"

create_closed() {
    gh issue create --repo "$REPO" "$@" --state closed 2>/dev/null || {
        local url
        url=$(gh issue create --repo "$REPO" "$@")
        local num="${url##*/}"
        gh issue close "$num" --repo "$REPO" --reason completed
        echo "closed #$num"
    }
}

create_open() {
    gh issue create --repo "$REPO" "$@"
}

echo "== Closed (реализовано в v1.1.0) =="

create_closed \
    --title "feat: autowiring по типам конструктора" \
    --label "feat,area:autowiring" \
    --milestone "v1.1.0" \
    --body "$(cat <<'EOF'
## Что сделано

- `Autowirer`, `enableAutowiring()`, `autowire()`, разрешение через `get(FQCN)`
- Union/nullable/default, внедрение `ContainerInterface`, детекция циклов

## Документация

- [Autowiring](https://github.com/cloudcastle-apps/di/wiki/Autowiring)
EOF
)"

create_closed \
    --title "feat: ClassScanner и Container::scan()" \
    --label "feat,area:scanner" \
    --milestone "v1.1.0" \
    --body "Рекурсивный scan каталогов, фильтр namespace, регистрация через autowire(). Wiki: https://github.com/cloudcastle-apps/di/wiki/Class-scanning"

create_closed \
    --title "feat: ContainerRegistry — глобальный реестр" \
    --label "feat,area:registry" \
    --milestone "v1.1.0" \
    --body "set/get/has/reset для singleton-контейнера приложения. Wiki: https://github.com/cloudcastle-apps/di/wiki/Global-registry"

create_closed \
    --title "feat: tagged services и декораторы" \
    --label "feat,area:container" \
    --milestone "v1.1.0" \
    --body "tag(), getTagged(), decorate(). Wiki: https://github.com/cloudcastle-apps/di/wiki/Tags-and-decorators"

create_closed \
    --title "docs: Wiki и doc/guide для v1.1.0" \
    --label "documentation,area:docs" \
    --milestone "v1.1.0" \
    --body "14 страниц Wiki, RST-руководство, README/CHANGELOG/UPGRADING."

echo "== Open =="

create_open \
    --title "release: чек-лист релиза v1.1.0" \
    --label "release,roadmap" \
    --milestone "v1.1.0" \
    --body "$(cat <<'EOF'
## Чек-лист

- [ ] `composer ci` зелёный
- [ ] CHANGELOG / UPGRADING финализированы
- [ ] Тег `v1.1.0` и GitHub Release
- [ ] Packagist обновлён (workflow)
- [ ] Wiki синхронизирована

## Связанные закрытые задачи

Autowiring, scan, registry, tags/decorators, docs — реализованы в main.
EOF
)"

create_open \
    --title "roadmap: v1.1.0 — обзор" \
    --label "roadmap" \
    --milestone "v1.1.0" \
    --body "$(cat <<'EOF'
Мilestone **v1.1.0** — расширение CloudCastle DI без breaking changes.

| Область | Статус |
|---------|--------|
| Autowiring | ✓ |
| ClassScanner / scan | ✓ |
| ContainerRegistry | ✓ |
| Tags / decorators | ✓ |
| Документация | ✓ |
| Релиз на Packagist | открыто (# release checklist) |

Wiki: https://github.com/cloudcastle-apps/di/wiki/Home
EOF
)"

create_open \
    --title "feat: PHP attributes для autowiring" \
    --label "feat,enhancement,area:autowiring" \
    --milestone "Backlog" \
    --body "Поддержка `#[Inject]`, `#[Autowire(service: ...)]` поверх reflection autowiring."

create_open \
    --title "feat: ClassScanner — поддержка enum" \
    --label "feat,enhancement,area:scanner" \
    --milestone "Backlog" \
    --body 'Парсинг enum в PHP-файлах при scan() (сейчас только class).'

create_open \
    --title "feat: autowiring по имени параметра" \
    --label "feat,enhancement,area:autowiring" \
    --milestone "Backlog" \
    --body 'Опциональное сопоставление параметра logger с LoggerInterface по convention (как в PHP-DI).'

create_open \
    --title "docs: примеры bootstrap (plain PHP, тесты, CLI)" \
    --label "documentation,area:docs,good first issue" \
    --milestone "Backlog" \
    --body "Добавить в Wiki или `doc/guide/` пошаговые composition root примеры."

create_open \
    --title "v2.0: описать breaking changes и миграцию" \
    --label "roadmap" \
    --milestone "v2.0" \
    --body "Подготовить UPGRADING и Discussion до major-релиза. См. README § 1.x → 2.0."

echo "Done."
