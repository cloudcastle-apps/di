#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
phpdoc_bin="$(mktemp)"
work_tmp="$(mktemp -d)"
output_tmp="${work_tmp}/output"
config_tmp="$(mktemp)"

cleanup() {
    rm -f "${phpdoc_bin}" "${config_tmp}"
    rm -rf "${work_tmp}"
}

trap cleanup EXIT

cp "${root}/vendor/bin/phpdoc" "${phpdoc_bin}"
chmod +x "${phpdoc_bin}"

mkdir -p "${output_tmp}" "${root}/docs" "${root}/var/cache/phpdoc"
cp -a "${root}/src" "${work_tmp}/src"
cp -a "${root}/doc/guide" "${work_tmp}/guide"

cat > "${config_tmp}" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<phpdocumentor configVersion="3" xmlns="https://www.phpdoc.org">
    <title>CloudCastle DI</title>
    <paths>
        <output>${output_tmp}</output>
        <cache>${root}/var/cache/phpdoc</cache>
    </paths>
    <version number="1.0.0">
        <api>
            <source dsn="${work_tmp}">
                <path>src</path>
            </source>
        </api>
        <guide format="rst">
            <source dsn="${work_tmp}">
                <path>guide</path>
            </source>
            <output>guide</output>
        </guide>
    </version>
</phpdocumentor>
EOF

"${phpdoc_bin}" run -c "${config_tmp}" --force --no-progress

if [[ ! -f "${output_tmp}/index.html" ]]; then
    echo "Ошибка: phpDocumentor не создал index.html" >&2
    exit 1
fi

preserve_tmp="$(mktemp -d)"
for marker in .nojekyll CNAME; do
    if [[ -f "${root}/docs/${marker}" ]]; then
        cp -a "${root}/docs/${marker}" "${preserve_tmp}/${marker}"
    fi
done

shopt -s dotglob nullglob
rm -rf "${root}/docs"/*
cp -a "${output_tmp}/." "${root}/docs/"

for marker in .nojekyll CNAME; do
    if [[ -f "${preserve_tmp}/${marker}" ]]; then
        cp -a "${preserve_tmp}/${marker}" "${root}/docs/${marker}"
    fi
done
rm -rf "${preserve_tmp}"

if [[ ! -f "${root}/docs/CNAME" && -f "${root}/CNAME" ]]; then
    cp "${root}/CNAME" "${root}/docs/CNAME"
fi

if [[ ! -f "${root}/docs/.nojekyll" ]]; then
    : > "${root}/docs/.nojekyll"
fi

php "${root}/tools/docs-check.php" "${root}/docs"
