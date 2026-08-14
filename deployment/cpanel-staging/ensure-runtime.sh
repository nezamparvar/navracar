#!/usr/bin/env bash

# Create only the Laravel runtime directories that must persist on staging.
# This helper never copies, removes, or replaces runtime contents.
ensure_staging_runtime_dirs() {
    local app_root="$1"
    local relative path
    local -a required=(
        'storage/app/public'
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
}
