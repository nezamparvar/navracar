#!/usr/bin/env bash

set -Eeuo pipefail
umask 022

if [[ $# -ne 3 ]]; then
    echo 'Usage: tools/build-cpanel-release.sh OUTPUT_DIR RELEASE_TAG SOURCE_COMMIT' >&2
    exit 2
fi

readonly OUTPUT_DIR="$1"
readonly RELEASE_TAG="$2"
readonly SOURCE_COMMIT="$3"
readonly REPO_ROOT="$(git rev-parse --show-toplevel)"
readonly ARTIFACT_BRANCH="${CPANEL_ARTIFACT_BRANCH:-cpanel-release}"
readonly ENVIRONMENT="${CPANEL_ENVIRONMENT:-production}"
readonly DEPLOYMENT_DIR="${CPANEL_DEPLOYMENT_DIR:-deployment/cpanel}"
readonly RELEASE_CANDIDATE="${CPANEL_RELEASE_CANDIDATE:-$RELEASE_TAG}"
readonly STAGING_STATUS="${CPANEL_STAGING_STATUS:-not-applicable}"

[[ "$RELEASE_TAG" =~ ^(v|rc-v)[0-9]+\.[0-9]+\.[0-9]+([-.][0-9A-Za-z.-]+)?$|^ci-[0-9a-f]{7,40}$ ]] || {
    echo "Invalid release identifier: $RELEASE_TAG" >&2
    exit 3
}
[[ "$SOURCE_COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'SOURCE_COMMIT must be a full Git SHA.' >&2; exit 4; }
[[ "$(git rev-parse HEAD)" == "$SOURCE_COMMIT" ]] || { echo 'Checked-out HEAD does not match SOURCE_COMMIT.' >&2; exit 5; }

for required in vendor/autoload.php public/build/manifest.json public/build/assets "$DEPLOYMENT_DIR/.cpanel.yml"; do
    [[ -e "$REPO_ROOT/$required" ]] || { echo "Missing production build input: $required" >&2; exit 6; }
done

if [[ -e "$OUTPUT_DIR" ]]; then
    echo "Output directory already exists: $OUTPUT_DIR" >&2
    exit 7
fi

mkdir -p "$OUTPUT_DIR/application" "$OUTPUT_DIR/public_html" "$OUTPUT_DIR/deployment"

# Export only tracked production source paths. This automatically excludes the
# local .env, test databases, caches, uploads, logs, sessions, and node_modules.
git archive --format=tar HEAD \
    .env.example artisan composer.json composer.lock \
    app bootstrap config database public resources routes storage \
    | tar -xf - -C "$OUTPUT_DIR/application"

# Add the two production artifacts intentionally absent from development Git.
cp -a vendor "$OUTPUT_DIR/application/vendor"
rm -rf "$OUTPUT_DIR/application/public/build"
cp -a public/build "$OUTPUT_DIR/application/public/build"

# The public payload is an explicit allowlist and contains no private Laravel
# source. Its index is the audited split-layout entry point.
cp -a public/build "$OUTPUT_DIR/public_html/build"
cp -a public/.htaccess public/favicon.ico public/robots.txt "$OUTPUT_DIR/public_html/"
cp -a "$DEPLOYMENT_DIR/public_html/index.php" "$OUTPUT_DIR/public_html/index.php"
cp -a "$DEPLOYMENT_DIR/.cpanel.yml" "$OUTPUT_DIR/.cpanel.yml"
cp -a "$DEPLOYMENT_DIR/deploy.sh" "$OUTPUT_DIR/deployment/deploy.sh"
if [[ "$ENVIRONMENT" == 'staging' ]]; then
    cp -a "$DEPLOYMENT_DIR/ensure-runtime.sh" "$OUTPUT_DIR/deployment/ensure-runtime.sh"
fi
chmod 0755 "$OUTPUT_DIR/deployment/deploy.sh"

generated_at="${CPANEL_GENERATED_AT:-$(date -u +'%Y-%m-%dT%H:%M:%SZ')}"
workflow_run="${CPANEL_WORKFLOW_RUN:-local}"
# Compute deterministic payload identities from file content, excluding
# provenance files and the manifest. This avoids a metadata/checksum cycle.
payload_checksum() {
    local root="$1"
    (
        cd "$OUTPUT_DIR"
        if [[ "$root" == 'public_html' ]]; then
            find "$root" -type f ! -name 'index.php' ! -name '.htaccess' ! -name 'robots.txt' -print0
        else
            find "$root" -type f ! -name '.cpanel-release.json' ! -name 'DEPLOYMENT-METADATA.json' -print0
        fi \
            | LC_ALL=C sort -z \
            | xargs -0 sha256sum \
            | sha256sum \
            | awk '{print $1}'
    )
}
application_checksum="$(payload_checksum application)"
public_checksum="$(payload_checksum public_html)"
artifact_sha="$(printf '%s\n%s\n' "$application_checksum" "$public_checksum" | sha256sum | awk '{print $1}')"
artifact_id="${RELEASE_CANDIDATE}-${SOURCE_COMMIT:0:12}-${artifact_sha:0:12}"
cat > "$OUTPUT_DIR/DEPLOYMENT-METADATA.json" <<EOF
{
  "artifact_branch": "$ARTIFACT_BRANCH",
  "environment": "$ENVIRONMENT",
  "release": "$RELEASE_TAG",
  "release_candidate": "$RELEASE_CANDIDATE",
  "staging_status": "$STAGING_STATUS",
  "source_commit": "$SOURCE_COMMIT",
  "source_main_commit": "$SOURCE_COMMIT",
  "artifact_id": "$artifact_id",
  "artifact_sha": "$artifact_sha",
  "application_checksum": "$application_checksum",
  "public_checksum": "$public_checksum",
  "generated_at_utc": "$generated_at",
  "workflow_run": "$workflow_run"
}
EOF
cp "$OUTPUT_DIR/DEPLOYMENT-METADATA.json" "$OUTPUT_DIR/application/.cpanel-release.json"

# Hash every deployable file plus the deployment controls and provenance. The
# manifest intentionally excludes itself.
(
    cd "$OUTPUT_DIR"
    find . -type f ! -path './SHA256SUMS.txt' -print0 \
        | LC_ALL=C sort -z \
        | xargs -0 sha256sum > SHA256SUMS.txt
)
