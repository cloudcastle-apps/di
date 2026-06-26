#!/usr/bin/env bash
# Создаёт метки, milestones и стартовые issues для cloudcastle-apps/di.
# Запуск: ./scripts/github-bootstrap.sh
# Требуется: gh auth login с scope repo

set -euo pipefail

REPO="${GITHUB_REPOSITORY:-cloudcastle-apps/di}"

create_label() {
    local name="$1" color="$2" description="$3"
    if gh label list --repo "$REPO" --json name --jq ".[] | select(.name==\"$name\") | .name" | grep -q .; then
        echo "label exists: $name"
        return 0
    fi
    gh label create "$name" --repo "$REPO" --color "$color" --description "$description"
}

echo "== Labels =="
create_label "feat" "0E8A16" "Новая функциональность"
create_label "fix" "D73A4A" "Исправление дефекта"
create_label "refactor" "C5DEF5" "Рефакторинг без смены поведения"
create_label "release" "FBCA04" "Подготовка или сопровождение релиза"
create_label "roadmap" "5319E7" "Roadmap и мета-задачи"
create_label "area:autowiring" "1D76DB" "Autowirer, autowire(), enableAutowiring()"
create_label "area:container" "006B75" "Container, tags, decorators"
create_label "area:scanner" "BFDADC" "ClassScanner, scan()"
create_label "area:registry" "E99695" "ContainerRegistry"
create_label "area:docs" "0075CA" "README, Wiki, doc/guide"
create_label "area:configuration" "6F42C1" "ContainerConfigurator, loaders, merger"

echo "== Milestones =="
create_milestone() {
    local title="$1" description="$2" due="${3:-}"
    if gh api "repos/$REPO/milestones" --jq ".[] | select(.title==\"$title\") | .number" | grep -q .; then
        echo "milestone exists: $title"
        return 0
    fi
    local args=(-f title="$title" -f description="$description" -f state=open)
    if [[ -n "$due" ]]; then
        args+=(-f due_on="$due")
    fi
    gh api "repos/$REPO/milestones" "${args[@]}"
}

create_milestone "v1.4.0" "freeze(), introspection, ergonomics" "2026-09-30T12:00:00Z"
create_milestone "v1.5.0" "ContainerConfigurator, registerAttribute, declarative config" "2026-10-15T12:00:00Z"
create_milestone "Backlog" "Идеи и улучшения без фиксированного релиза"
create_milestone "v2.0" "Breaking changes (major)"

echo "Done. Issues создаются отдельно (см. scripts/github-create-issues.sh)."
