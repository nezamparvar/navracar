#!/usr/bin/env bash

set -Eeuo pipefail

readonly ROOT="$(mktemp -d)"
trap 'rm -rf -- "$ROOT"' EXIT
readonly APP_ROOT="$ROOT/app"
readonly PUBLIC_ROOT="$ROOT/public"
source deployment/cpanel-staging/ensure-runtime.sh

# A completely missing runtime tree is created.
ensure_staging_runtime_dirs "$APP_ROOT"
for path in \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs; do
    [[ -d "$APP_ROOT/$path" && -w "$APP_ROOT/$path" ]] || exit 10
done

# The cPanel-served public disk is also created and validated independently.
ensure_staging_runtime_dirs "$APP_ROOT" "$PUBLIC_ROOT/storage"
[[ -d "$PUBLIC_ROOT/storage" && -w "$PUBLIC_ROOT/storage" ]] || exit 14
printf 'staging upload\n' > "$PUBLIC_ROOT/storage/keep.txt"
ensure_staging_runtime_dirs "$APP_ROOT" "$PUBLIC_ROOT/storage"
[[ "staging upload" == "$(cat "$PUBLIC_ROOT/storage/keep.txt")" ]] || exit 15

# Existing persistent files survive preparation byte-for-byte.
printf 'uploaded image\n' > "$APP_ROOT/storage/app/public/vehicle.jpg"
printf 'existing log\n' > "$APP_ROOT/storage/logs/app.log"
before_upload="$(sha256sum "$APP_ROOT/storage/app/public/vehicle.jpg")"
before_log="$(sha256sum "$APP_ROOT/storage/logs/app.log")"
ensure_staging_runtime_dirs "$APP_ROOT"
[[ "$before_upload" == "$(sha256sum "$APP_ROOT/storage/app/public/vehicle.jpg")" ]] || exit 11
[[ "$before_log" == "$(sha256sum "$APP_ROOT/storage/logs/app.log")" ]] || exit 12

# A path that cannot be made a directory fails safely.
readonly BROKEN_ROOT="$ROOT/broken"
mkdir -p "$BROKEN_ROOT/storage/app"
printf 'not a directory\n' > "$BROKEN_ROOT/storage/app/public"
if ensure_staging_runtime_dirs "$BROKEN_ROOT"; then
    echo 'Expected runtime preparation to fail for a file/directory collision.' >&2
    exit 13
fi

echo 'Staging runtime directory tests passed.'
