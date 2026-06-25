#!/usr/bin/env bash
# Создаёт рекомендуемые Issue Views в GitHub UI (Firefox + xdotool).
# Запуск: ./scripts/github-create-issue-views.sh
# Требуется: DISPLAY, Firefox, xdotool, gh auth (для проверки доступа).

set -euo pipefail

REPO="${GITHUB_REPOSITORY:-cloudcastle-apps/di}"
OWNER="${REPO%%/*}"
NAME="${REPO##*/}"
BASE_URL="https://github.com/${REPO}/issues/views/new"

if ! command -v xdotool >/dev/null 2>&1; then
    echo "Нужен xdotool." >&2
    exit 1
fi

if ! command -v firefox >/dev/null 2>&1; then
    echo "Нужен Firefox." >&2
    exit 1
fi

if [[ -z "${DISPLAY:-}" ]]; then
    export DISPLAY=:0
fi

gh auth status >/dev/null

create_view() {
    local title="$1"
    local query="$2"

    echo "→ View: ${title}"

    firefox -new-tab "${BASE_URL}" >/dev/null 2>&1 &
    sleep 4

    xdotool search --name "New view · ${OWNER}/${NAME}" windowactivate
    sleep 0.5

    xdotool key ctrl+a
    xdotool type --delay 12 "${title}"
    xdotool key Tab
    sleep 0.3
    xdotool type --delay 8 "${query}"
    sleep 0.5

    # Save view (кнопка «Create view» / «Save»)
    xdotool key Tab Tab Return
    sleep 3
}

create_view "Open roadmap" 'is:issue is:open milestone:v1.1.0'
create_view "Backlog" 'is:issue is:open milestone:Backlog'
create_view "Good first issue" 'is:issue is:open label:"good first issue"'
create_view "Bugs" 'is:issue is:open label:bug'

echo "Готово. Проверьте: https://github.com/${REPO}/issues/views"
