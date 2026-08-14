#!/usr/bin/env bash

set -Eeuo pipefail

if [[ $# -ne 1 ]]; then
    echo 'Usage: tools/validate-cpanel-release.sh ARTIFACT_DIR' >&2
    exit 2
fi

readonly ARTIFACT_DIR="$(cd "$1" && pwd -P)"

required_files=(
    '.cpanel.yml'
    'DEPLOYMENT-METADATA.json'
    'SHA256SUMS.txt'
    'deployment/deploy.sh'
    'application/.cpanel-release.json'
    'application/vendor/autoload.php'
    'application/public/build/manifest.json'
    'public_html/build/manifest.json'
    'public_html/index.php'
    'public_html/.htaccess'
    'public_html/favicon.ico'
    'public_html/robots.txt'
)

for file in "${required_files[@]}"; do
    [[ -f "$ARTIFACT_DIR/$file" ]] || { echo "Missing required artifact file: $file" >&2; exit 10; }
done

# Prefer Ruby on CI (available on ubuntu-latest). The PHP fallback keeps the
# validator usable in this Laravel checkout without adding a parser to the
# production artifact.
if command -v ruby >/dev/null 2>&1; then
    ruby -e 'require "yaml"; value = YAML.safe_load(File.read(ARGV[0])); abort "invalid deployment tasks" unless value.is_a?(Hash) && value.dig("deployment", "tasks").is_a?(Array) && !value.dig("deployment", "tasks").empty?' "$ARTIFACT_DIR/.cpanel.yml"
elif PHP_BIN="${PHP_BIN:-php}"; command -v "$PHP_BIN" >/dev/null 2>&1 && "$PHP_BIN" -r 'require "vendor/autoload.php"; exit(class_exists("Symfony\\Component\\Yaml\\Yaml") ? 0 : 1);' 2>/dev/null; then
    "$PHP_BIN" -r 'require "vendor/autoload.php"; $value = Symfony\Component\Yaml\Yaml::parseFile($argv[1]); if (!is_array($value) || !isset($value["deployment"]["tasks"]) || !is_array($value["deployment"]["tasks"]) || !$value["deployment"]["tasks"]) exit(1);' "$ARTIFACT_DIR/.cpanel.yml"
else
    # Last-resort structural validation for minimal hosting workstations. The
    # CI path above still performs a full YAML parse before publication.
    awk '
        NR == 1 && $0 == "---" { header = 1 }
        $0 == "deployment:" { deployment = 1 }
        $0 == "  tasks:" { tasks = 1 }
        $0 ~ /^    - / { count += 1 }
        END { exit !(header && deployment && tasks && count > 0) }
    ' "$ARTIFACT_DIR/.cpanel.yml" || {
        echo 'The .cpanel.yml file does not have the expected cPanel deployment structure.' >&2
        exit 16
    }
fi

grep -Fq '/home/navrac/navracar-app' "$ARTIFACT_DIR/.cpanel.yml"
grep -Fq '/home/navrac/public_html' "$ARTIFACT_DIR/.cpanel.yml"
grep -Fq '../navracar-app/vendor/autoload.php' "$ARTIFACT_DIR/public_html/index.php"
grep -Fq '../navracar-app/bootstrap/app.php' "$ARTIFACT_DIR/public_html/index.php"

# public_html must be a strict allowlist.
mapfile -t public_roots < <(find "$ARTIFACT_DIR/public_html" -mindepth 1 -maxdepth 1 -printf '%f\n' | LC_ALL=C sort)
expected_public=( '.htaccess' 'build' 'favicon.ico' 'index.php' 'robots.txt' )
[[ "${public_roots[*]}" == "${expected_public[*]}" ]] || {
    echo "Unexpected public_html root items: ${public_roots[*]}" >&2
    exit 11
}

# Only empty tracked skeleton markers may exist under application/storage.
if find "$ARTIFACT_DIR/application/storage" -type f ! -name '.gitignore' -print -quit | grep -q .; then
    echo 'Persistent storage content is present in the artifact.' >&2
    exit 12
fi

for forbidden in \
    "$ARTIFACT_DIR/application/.env" \
    "$ARTIFACT_DIR/.env" \
    "$ARTIFACT_DIR/application/database/database.sqlite" \
    "$ARTIFACT_DIR/application/database/e2e.sqlite" \
    "$ARTIFACT_DIR/node_modules" \
    "$ARTIFACT_DIR/application/node_modules" \
    "$ARTIFACT_DIR/.git" \
    "$ARTIFACT_DIR/application/tests" \
    "$ARTIFACT_DIR/application/test-results"; do
    [[ ! -e "$forbidden" ]] || { echo "Forbidden artifact path exists: $forbidden" >&2; exit 13; }
done

if find "$ARTIFACT_DIR" -type f \( -name '*.sqlite' -o -name '*.log' -o -name '.env' \) -print -quit | grep -q .; then
    echo 'A database, log, or .env file is present in the artifact.' >&2
    exit 14
fi

# Guard the safety contract in the actual deployment script.
grep -Fq "[[ -f \"\$APP_ROOT/.env\" ]]" "$ARTIFACT_DIR/deployment/deploy.sh"
grep -Fq "[[ -d \"\$APP_ROOT/storage\" ]]" "$ARTIFACT_DIR/deployment/deploy.sh"
grep -Fq "\$PUBLIC_ROOT/storage" "$ARTIFACT_DIR/deployment/deploy.sh"
if grep -Eq 'cp[[:space:]].*public_html/(app|bootstrap|config|database|resources|routes|storage)' "$ARTIFACT_DIR/deployment/deploy.sh"; then
    echo 'Deployment script could expose private or persistent application paths under public_html.' >&2
    exit 15
fi

(
    cd "$ARTIFACT_DIR"
    sha256sum --check --strict SHA256SUMS.txt
)

echo 'cPanel deployment artifact validation passed.'
