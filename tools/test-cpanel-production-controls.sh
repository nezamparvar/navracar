#!/usr/bin/env bash

set -Eeuo pipefail

readonly ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
readonly CONTROLS="$ROOT/deployment/cpanel/public_html"
readonly PUBLIC_SOURCE="$ROOT/public"

for file in .htaccess index.php favicon.ico robots.txt; do
    [[ -f "$CONTROLS/$file" ]] || {
        echo "Missing production deployment control: deployment/cpanel/public_html/$file" >&2
        exit 10
    }
done

cmp -s "$CONTROLS/.htaccess" "$PUBLIC_SOURCE/.htaccess" || {
    echo 'Production deployment .htaccess differs from the audited production public/.htaccess.' >&2
    exit 11
}
cmp -s "$CONTROLS/robots.txt" "$PUBLIC_SOURCE/robots.txt" || {
    echo 'Production deployment robots.txt differs from public/robots.txt.' >&2
    exit 12
}

grep -Fq "../navracar-app/vendor/autoload.php" "$CONTROLS/index.php"
grep -Fq "../navracar-app/bootstrap/app.php" "$CONTROLS/index.php"
! grep -Fq '/staging' "$CONTROLS/index.php"
grep -Fq 'RewriteRule ^ index.php [L]' "$CONTROLS/.htaccess"
grep -Fq 'RewriteCond %{REQUEST_FILENAME} !-f' "$CONTROLS/.htaccess"
! grep -Fq '/staging' "$CONTROLS/.htaccess"
! grep -Eiq '(^|[[:space:]])(app|bootstrap|config|database|resources|routes|storage)(/|[[:space:]])' "$CONTROLS/.htaccess"

readonly ARTIFACT="$(mktemp -d)"
trap 'rm -rf -- "$ARTIFACT"' EXIT
mkdir -p "$ARTIFACT/public_html/build"
cp "$CONTROLS/.htaccess" "$CONTROLS/index.php" "$CONTROLS/favicon.ico" "$CONTROLS/robots.txt" "$ARTIFACT/public_html/"
touch "$ARTIFACT/public_html/build/manifest.json"

for file in .htaccess index.php build/manifest.json favicon.ico robots.txt; do
    [[ -f "$ARTIFACT/public_html/$file" ]] || {
        echo "Production deployment tree missing public_html/$file" >&2
        exit 14
    }
done

echo 'Production cPanel public-root controls validated.'
