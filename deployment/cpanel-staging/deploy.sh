#!/usr/bin/env bash

set -Eeuo pipefail
umask 022

readonly PRODUCTION_APP_ROOT='/home/navrac/navracar-app'
readonly PRODUCTION_PUBLIC_ROOT='/home/navrac/public_html'
readonly EXPECTED_APP_ROOT='/home/navrac/navracar-staging-app'
readonly EXPECTED_PUBLIC_ROOT='/home/navrac/public_html/staging'
readonly APP_ROOT="${1:-}"
readonly PUBLIC_ROOT="${2:-}"

if [[ "$APP_ROOT" != "$EXPECTED_APP_ROOT" || "$PUBLIC_ROOT" != "$EXPECTED_PUBLIC_ROOT" ]]; then
    echo 'Refusing staging deployment: destination paths do not match the reviewed staging layout.' >&2
    exit 20
fi
if [[ "$APP_ROOT" == "$PRODUCTION_APP_ROOT" || "$PUBLIC_ROOT" == "$PRODUCTION_PUBLIC_ROOT" ]]; then
    echo 'Refusing staging deployment: production destination supplied.' >&2
    exit 21
fi

readonly ARTIFACT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
source "$ARTIFACT_ROOT/deployment/ensure-runtime.sh"
readonly APP_PAYLOAD="$ARTIFACT_ROOT/application"
readonly PUBLIC_PAYLOAD="$ARTIFACT_ROOT/public_html"
readonly APP_STAGE='/home/navrac/.navracar-staging-app-stage'
readonly PUBLIC_STAGE='/home/navrac/.staging-navracar-public-stage'
readonly APP_BACKUP='/home/navrac/.navracar-staging-app-previous'
readonly PUBLIC_BACKUP='/home/navrac/.staging-navracar-public-previous'

# Staging has its own .env, storage, uploads, logs, sessions, and public
# storage path. None of those persistent items is part of the replacement set.
readonly -a APP_ITEMS=(
    '.env.example'
    '.cpanel-release.json'
    'artisan'
    'composer.json'
    'composer.lock'
    'app'
    'bootstrap'
    'config'
    'database'
    'public'
    'resources'
    'routes'
    'vendor'
)
readonly -a PUBLIC_ITEMS=(
    'build'
    'index.php'
    '.htaccess'
    'favicon.ico'
    'robots.txt'
)

for required in \
    "$APP_PAYLOAD/vendor/autoload.php" \
    "$APP_PAYLOAD/public/build/manifest.json" \
    "$PUBLIC_PAYLOAD/build/manifest.json" \
    "$PUBLIC_PAYLOAD/index.php" \
    "$ARTIFACT_ROOT/SHA256SUMS.txt"; do
    [[ -f "$required" ]] || { echo "Refusing staging deployment: missing $required" >&2; exit 22; }
done

[[ -f "$APP_ROOT/.env" ]] || { echo 'Refusing staging deployment: staging .env is missing.' >&2; exit 23; }
[[ -d "$APP_ROOT/storage" ]] || { echo 'Refusing staging deployment: staging storage/ is missing.' >&2; exit 24; }
[[ -e "$PUBLIC_ROOT/storage" || -L "$PUBLIC_ROOT/storage" ]] || {
    echo 'Refusing staging deployment: staging public storage path is missing.' >&2
    exit 25
}

# Git cannot track empty directories. Prepare missing Laravel runtime paths
# in-place before any managed release item is swapped, preserving all files.
ensure_staging_runtime_dirs "$APP_ROOT" || exit 26

rm -rf -- "$APP_STAGE" "$PUBLIC_STAGE" "$APP_BACKUP" "$PUBLIC_BACKUP"
mkdir -p -- "$APP_STAGE" "$PUBLIC_STAGE" "$APP_BACKUP" "$PUBLIC_BACKUP"

for item in "${APP_ITEMS[@]}"; do cp -a -- "$APP_PAYLOAD/$item" "$APP_STAGE/$item"; done
for item in "${PUBLIC_ITEMS[@]}"; do cp -a -- "$PUBLIC_PAYLOAD/$item" "$PUBLIC_STAGE/$item"; done

grep -Fq "../../navracar-staging-app/vendor/autoload.php" "$PUBLIC_STAGE/index.php"
grep -Fq "../../navracar-staging-app/bootstrap/app.php" "$PUBLIC_STAGE/index.php"

app_swapped=()
public_swapped=()
rollback_partial_swap() {
    set +e
    local index item
    for ((index=${#public_swapped[@]}-1; index>=0; index--)); do
        item="${public_swapped[$index]}"
        rm -rf -- "$PUBLIC_ROOT/$item"
        [[ -e "$PUBLIC_BACKUP/$item" || -L "$PUBLIC_BACKUP/$item" ]] && mv -- "$PUBLIC_BACKUP/$item" "$PUBLIC_ROOT/$item"
    done
    for ((index=${#app_swapped[@]}-1; index>=0; index--)); do
        item="${app_swapped[$index]}"
        rm -rf -- "$APP_ROOT/$item"
        [[ -e "$APP_BACKUP/$item" || -L "$APP_BACKUP/$item" ]] && mv -- "$APP_BACKUP/$item" "$APP_ROOT/$item"
    done
    echo 'Staging deployment failed; managed items were rolled back. Persistent staging state was not touched.' >&2
}
trap rollback_partial_swap ERR

for item in "${APP_ITEMS[@]}"; do
    app_swapped+=("$item")
    if [[ -e "$APP_ROOT/$item" || -L "$APP_ROOT/$item" ]]; then mv -- "$APP_ROOT/$item" "$APP_BACKUP/$item"; fi
    mv -- "$APP_STAGE/$item" "$APP_ROOT/$item"
done
for item in "${PUBLIC_ITEMS[@]}"; do
    public_swapped+=("$item")
    if [[ -e "$PUBLIC_ROOT/$item" || -L "$PUBLIC_ROOT/$item" ]]; then mv -- "$PUBLIC_ROOT/$item" "$PUBLIC_BACKUP/$item"; fi
    mv -- "$PUBLIC_STAGE/$item" "$PUBLIC_ROOT/$item"
done

trap - ERR
rmdir -- "$APP_STAGE" "$PUBLIC_STAGE"
echo 'Staging deployment completed. Staging .env, storage/, uploads, and public storage were preserved.'
