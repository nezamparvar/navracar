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

[[ "$RELEASE_TAG" =~ ^v[0-9]+\.[0-9]+\.[0-9]+([-.][0-9A-Za-z.-]+)?$|^ci-[0-9a-f]{7,40}$ ]] || {
    echo "Invalid release identifier: $RELEASE_TAG" >&2
    exit 3
}
[[ "$SOURCE_COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'SOURCE_COMMIT must be a full Git SHA.' >&2; exit 4; }
[[ "$(git rev-parse HEAD)" == "$SOURCE_COMMIT" ]] || { echo 'Checked-out HEAD does not match SOURCE_COMMIT.' >&2; exit 5; }

for required in vendor/autoload.php public/build/manifest.json public/build/assets deployment/cpanel/.cpanel.yml; do
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
cp -a deployment/cpanel/public_html/index.php "$OUTPUT_DIR/public_html/index.php"
cp -a deployment/cpanel/.cpanel.yml "$OUTPUT_DIR/.cpanel.yml"
cp -a deployment/cpanel/deploy.sh "$OUTPUT_DIR/deployment/deploy.sh"
chmod 0755 "$OUTPUT_DIR/deployment/deploy.sh"

generated_at="${CPANEL_GENERATED_AT:-$(date -u +'%Y-%m-%dT%H:%M:%SZ')}"
workflow_run="${CPANEL_WORKFLOW_RUN:-local}"
cat > "$OUTPUT_DIR/DEPLOYMENT-METADATA.json" <<EOF
{
  "artifact_branch": "cpanel-release",
  "release": "$RELEASE_TAG",
  "source_main_commit": "$SOURCE_COMMIT",
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
