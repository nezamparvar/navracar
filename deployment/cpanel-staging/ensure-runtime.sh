#!/usr/bin/env bash

# Create only the Laravel runtime directories that must persist on staging.
# This helper never copies, removes, or replaces runtime contents.
ensure_staging_runtime_dirs() {
    local app_root="$1"
    local public_disk_root="${2:-}"
    local relative path
    local -a required=(
        'storage/app/public'
        'storage/fonts'
        'storage/framework/cache/data'
        'storage/framework/sessions'
        'storage/framework/views'
        'storage/logs'
    )

    for relative in "${required[@]}"; do
        path="$app_root/$relative"
        mkdir -p -- "$path" || {
            echo "Refusing staging deployment: cannot create $path." >&2
            return 1
        }
        [[ -d "$path" && -w "$path" ]] || {
            echo "Refusing staging deployment: runtime path is not writable: $path." >&2
            return 1
        }
    done

    # PUBLIC_DISK_ROOT is an optional staging-only path. When configured, it
    # is the directory served as /staging/storage and must be writable too.
    if [[ -n "$public_disk_root" ]]; then
        mkdir -p -- "$public_disk_root" || {
            echo "Refusing staging deployment: cannot create public disk root $public_disk_root." >&2
            return 1
        }
        [[ -d "$public_disk_root" && -w "$public_disk_root" ]] || {
            echo "Refusing staging deployment: public disk root is not writable: $public_disk_root." >&2
            return 1
        }
    fi
}

# cPanel Git deployments do not inherit the MultiPHP web handler path. Locate
# an explicit PHP 8.3+ CLI so the owner never needs SSH or cPanel Terminal.
resolve_staging_php_bin() {
    local candidate path_php
    local -a candidates=()

    if [[ -n "${NAVRACAR_PHP_BIN:-}" ]]; then
        candidates+=("$NAVRACAR_PHP_BIN")
    fi

    candidates+=(
        '/opt/cpanel/ea-php84/root/usr/bin/php'
        '/opt/cpanel/ea-php83/root/usr/bin/php'
        '/usr/local/bin/ea-php84'
        '/usr/local/bin/ea-php83'
    )

    path_php="$(command -v php 2>/dev/null || true)"
    [[ -n "$path_php" ]] && candidates+=("$path_php")

    for candidate in "${candidates[@]}"; do
        if [[ -x "$candidate" ]] && "$candidate" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'; then
            printf '%s\n' "$candidate"
            return 0
        fi
    done

    return 1
}
