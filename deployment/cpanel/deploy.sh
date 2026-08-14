#!/usr/bin/env bash

set -Eeuo pipefail
umask 022

readonly EXPECTED_APP_ROOT='/home/navrac/navracar-app'
readonly EXPECTED_PUBLIC_ROOT='/home/navrac/public_html'
readonly APP_ROOT="${1:-}"
readonly PUBLIC_ROOT="${2:-}"

if [[ "$APP_ROOT" != "$EXPECTED_APP_ROOT" || "$PUBLIC_ROOT" != "$EXPECTED_PUBLIC_ROOT" ]]; then
    echo 'Refusing deployment: destination paths do not match the audited production layout.' >&2
    exit 20
fi

readonly ARTIFACT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
readonly APP_PAYLOAD="$ARTIFACT_ROOT/application"
readonly PUBLIC_PAYLOAD="$ARTIFACT_ROOT/public_html"
readonly APP_STAGE='/home/navrac/.navracar-app-cpanel-stage'
readonly PUBLIC_STAGE='/home/navrac/.public-html-cpanel-stage'
readonly APP_BACKUP='/home/navrac/.navracar-app-cpanel-previous'
readonly PUBLIC_BACKUP='/home/navrac/.public-html-cpanel-previous'

# Only these application items are managed. In particular, .env and storage/
# are deliberately absent so production credentials and writable data survive.
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

# Only these web-root items are managed. public_html/storage and every other
# unrelated web-root item are deliberately untouched.
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
    [[ -f "$required" ]] || { echo "Refusing deployment: missing $required" >&2; exit 21; }
done

# This pipeline manages an existing production installation. Stop rather than
# manufacture or replace production-specific state.
[[ -f "$APP_ROOT/.env" ]] || { echo 'Refusing deployment: production .env is missing.' >&2; exit 22; }
[[ -d "$APP_ROOT/storage" ]] || { echo 'Refusing deployment: production storage/ is missing.' >&2; exit 23; }
[[ -e "$PUBLIC_ROOT/storage" || -L "$PUBLIC_ROOT/storage" ]] || {
    echo 'Refusing deployment: public_html/storage is missing.' >&2
    exit 24
}

rm -rf -- "$APP_STAGE" "$PUBLIC_STAGE" "$APP_BACKUP" "$PUBLIC_BACKUP"
mkdir -p -- "$APP_STAGE" "$PUBLIC_STAGE" "$APP_BACKUP" "$PUBLIC_BACKUP"

# Stage and validate the complete replacement set before touching live code.
for item in "${APP_ITEMS[@]}"; do
    cp -a -- "$APP_PAYLOAD/$item" "$APP_STAGE/$item"
done
for item in "${PUBLIC_ITEMS[@]}"; do
    cp -a -- "$PUBLIC_PAYLOAD/$item" "$PUBLIC_STAGE/$item"
done

[[ -f "$APP_STAGE/vendor/autoload.php" ]]
[[ -f "$PUBLIC_STAGE/build/manifest.json" ]]
grep -Fq "../navracar-app/vendor/autoload.php" "$PUBLIC_STAGE/index.php"
grep -Fq "../navracar-app/bootstrap/app.php" "$PUBLIC_STAGE/index.php"

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

    echo 'Deployment failed; managed items were rolled back. Persistent state was not touched.' >&2
}
trap rollback_partial_swap ERR

# Replace only managed application items. Each old item remains in the
# cpanel-previous backup until the next successful deployment.
for item in "${APP_ITEMS[@]}"; do
    app_swapped+=("$item")
    if [[ -e "$APP_ROOT/$item" || -L "$APP_ROOT/$item" ]]; then
        mv -- "$APP_ROOT/$item" "$APP_BACKUP/$item"
    fi
    mv -- "$APP_STAGE/$item" "$APP_ROOT/$item"
done

# Replace only the five audited public items. The storage link and unrelated
# public_html content are not listed and cannot be moved or overwritten here.
for item in "${PUBLIC_ITEMS[@]}"; do
    public_swapped+=("$item")
    if [[ -e "$PUBLIC_ROOT/$item" || -L "$PUBLIC_ROOT/$item" ]]; then
        mv -- "$PUBLIC_ROOT/$item" "$PUBLIC_BACKUP/$item"
    fi
    mv -- "$PUBLIC_STAGE/$item" "$PUBLIC_ROOT/$item"
done

trap - ERR
rmdir -- "$APP_STAGE" "$PUBLIC_STAGE"

echo 'cPanel deployment completed. Production .env, storage/, and public_html/storage were preserved.'
