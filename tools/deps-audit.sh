#!/usr/bin/env bash
set -euo pipefail

attempts="${DEPS_AUDIT_ATTEMPTS:-3}"
pause_seconds="${DEPS_AUDIT_PAUSE_SECONDS:-5}"

for ((attempt = 1; attempt <= attempts; attempt++)); do
    if composer audit --no-ansi "$@"; then
        exit 0
    fi

    exit_code=$?

    if ((attempt >= attempts)); then
        echo "composer audit: все ${attempts} попыток неудачны (exit ${exit_code})" >&2
        exit "${exit_code}"
    fi

    echo "composer audit: попытка ${attempt}/${attempts} неудачна, повтор через ${pause_seconds}s..." >&2
    sleep "${pause_seconds}"
done
