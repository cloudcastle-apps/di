#!/usr/bin/env bash
# Issues для релиза v1.5.0 (cloudcastle-apps/di).
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

echo "== Closed (реализовано в v1.5.0) =="

create_closed \
    --title "feat: ContainerConfigurator — конфигурация из PHP/JSON/YAML/XML" \
    --label "feat,area:configuration" \
    --milestone "v1.5.0" \
    --body "$(cat <<'EOF'
## Что сделано

- `ContainerConfigurator`, загрузчики, `ConfigurationMerger`, `ConfigurationApplicator`
- Секции: services, bind, aliases, autowire, tags, scan, register_attributes, autowiring
- Приоритеты: параметр → файл → порядок источников

## Документация

- https://github.com/cloudcastle-apps/di/wiki/Configuration
EOF
)"

create_closed \
    --title "feat: registerAttribute() — пользовательские PHP attributes" \
    --label "feat,area:autowiring" \
    --milestone "v1.5.0" \
    --body "Контракт `ServiceIdAttribute`, `AttributeServiceIdRegistry`, интеграция с MemberResolver. Wiki: https://github.com/cloudcastle-apps/di/wiki/Autowiring"

create_closed \
    --title "docs: диаграммы и документация v1.5.0" \
    --label "documentation,area:docs" \
    --milestone "v1.5.0" \
    --body "375 тестов, расширенные Mermaid-схемы в Wiki/Architecture, README, doc/guide/configuration.rst."

echo "== Open =="

create_open \
    --title "roadmap: v1.5.0 — обзор" \
    --label "roadmap" \
    --milestone "v1.5.0" \
    --body "$(cat <<'EOF'
Milestone **v1.5.0** — декларативная конфигурация и custom attributes без breaking changes.

| Область | Статус |
|---------|--------|
| ContainerConfigurator (PHP/JSON/YAML/XML) | ✓ |
| registerAttribute() | ✓ |
| Документация и диаграммы | ✓ |
| Релиз на Packagist | открыто (# release checklist) |

Wiki: https://github.com/cloudcastle-apps/di/wiki/Home
EOF
)"

create_open \
    --title "release: чек-лист релиза v1.5.0" \
    --label "release,roadmap" \
    --milestone "v1.5.0" \
    --body "$(cat <<'EOF'
## Чек-лист

- [ ] `composer ci` зелёный
- [ ] CHANGELOG / UPGRADING финализированы
- [ ] Тег `v1.5.0` и GitHub Release
- [ ] Packagist обновлён (workflow)
- [ ] Wiki синхронизирована

## Связанные задачи

ContainerConfigurator, registerAttribute, docs — в main.
EOF
)"

echo "Done."
