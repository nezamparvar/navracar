#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mobile_index="$project_root/mobile/index.html"
apk_source="$project_root/android/app/build/outputs/apk/debug/app-debug.apk"
output_dir="$project_root/android/app/build/outputs"
original_index="$(mktemp)"

cp "$mobile_index" "$original_index"
trap 'cp "$original_index" "$mobile_index"; rm -f "$original_index"' EXIT

build_variant() {
    local environment="$1"
    local api_base="$2"

    cp "$original_index" "$mobile_index"
    node - "$mobile_index" "$environment" "$api_base" <<'NODE'
const fs = require('fs');
const [file, environment, apiBase] = process.argv.slice(2);
let html = fs.readFileSync(file, 'utf8');
html = html.replace(
  /<meta name="navracar-api-base" content="[^"]+">/,
  `<meta name="navracar-api-base" content="${apiBase}">`,
);
html = html.replace(
  /<meta name="navracar-environment" content="[^"]+">/,
  `<meta name="navracar-environment" content="${environment}">`,
);
fs.writeFileSync(file, html);
NODE

    (cd "$project_root" && npx cap sync android)
    if [[ "$environment" == "staging" ]]; then
        (cd "$project_root/android" && ./gradlew assembleDebug -PstagingBuild=true)
    else
        (cd "$project_root/android" && ./gradlew assembleDebug)
    fi
    mkdir -p "$output_dir"
    cp "$apk_source" "$output_dir/navracar-${environment}-debug.apk"
}

build_variant staging 'https://navracar.com/staging'
build_variant production 'https://navracar.com'

test -s "$output_dir/navracar-staging-debug.apk"
test -s "$output_dir/navracar-production-debug.apk"
