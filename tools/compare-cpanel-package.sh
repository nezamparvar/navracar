#!/usr/bin/env bash

set -Eeuo pipefail

if [[ $# -ne 3 ]]; then
    echo 'Usage: tools/compare-cpanel-package.sh ARTIFACT_DIR APP_ZIP PUBLIC_ZIP' >&2
    exit 2
fi

readonly ARTIFACT_DIR="$(cd "$1" && pwd -P)"
readonly APP_ZIP="$(cd "$(dirname "$2")" && pwd -P)/$(basename "$2")"
readonly PUBLIC_ZIP="$(cd "$(dirname "$3")" && pwd -P)/$(basename "$3")"
readonly TEMP_ROOT="$(mktemp -d)"
trap 'rm -rf -- "$TEMP_ROOT"' EXIT

mkdir -p "$TEMP_ROOT/app" "$TEMP_ROOT/public"
unzip -q "$APP_ZIP" -d "$TEMP_ROOT/app"
unzip -q "$PUBLIC_ZIP" -d "$TEMP_ROOT/public"

# Project-owned executable/configuration content must be byte-identical to the
# already verified package. Runtime skeleton markers are checked separately.
for path in .env.example artisan composer.json composer.lock app bootstrap/app.php bootstrap/providers.php config database/factories database/migrations database/seeders public resources routes; do
    diff -qr "$TEMP_ROOT/app/$path" "$ARTIFACT_DIR/application/$path"
done

diff -qr "$TEMP_ROOT/public/build" "$ARTIFACT_DIR/public_html/build"
for path in .htaccess favicon.ico index.php robots.txt; do
    cmp "$TEMP_ROOT/public/$path" "$ARTIFACT_DIR/public_html/$path"
done

# Composer installations may contain platform-specific metadata, so compare
# the locked production package name/version set rather than archive bytes.
PHP_BIN="${PHP_BIN:-php}"
"$PHP_BIN" -r '
function packages(string $path): array {
    $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $rows = $data["packages"] ?? $data;
    $result = [];
    foreach ($rows as $row) {
        if (isset($row["name"], $row["version"])) $result[$row["name"]] = $row["version"];
    }
    ksort($result);
    return $result;
}
$left = packages($argv[1]);
$right = packages($argv[2]);
if ($left !== $right) {
    fwrite(STDERR, "Production Composer package set differs from the verified release package.\n");
    exit(1);
}
' "$TEMP_ROOT/app/vendor/composer/installed.json" "$ARTIFACT_DIR/application/vendor/composer/installed.json"

echo 'Generated artifact matches the verified cPanel package executable content.'
