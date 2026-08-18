#!/usr/bin/env bash

set -Eeuo pipefail

readonly ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
readonly WORKFLOW="$ROOT/.github/workflows/cpanel-staging.yml"

grep -Fq "if: github.ref == 'refs/heads/main'" "$WORKFLOW"
grep -Fq 'ref: main' "$WORKFLOW"
grep -Fq 'SOURCE_REF: main' "$WORKFLOW"
grep -Fq 'refs/heads/main:refs/remotes/origin/main' "$WORKFLOW"
grep -Fq 'refs/remotes/origin/main' "$WORKFLOW"

if grep -Fq 'SOURCE_REF: ${{ github.ref_name }}' "$WORKFLOW"; then
    echo 'Staging publishing must not accept the selected workflow branch as its source.' >&2
    exit 10
fi

if grep -Fq 'ref: ${{ github.ref_name }}' "$WORKFLOW"; then
    echo 'Staging publishing must be pinned to main.' >&2
    exit 11
fi

echo 'Staging source policy is pinned to the exact main HEAD.'
