#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
version="$(tr -d '[:space:]' < "$project_root/VERSION")"
artifacts="$project_root/artifacts"
staging="$(mktemp -d)"
plugin_root="$staging/ConferenceDiscountEligibility"

cleanup() {
    rm -rf "$staging"
}
trap cleanup EXIT

mkdir -p "$artifacts" "$plugin_root/vendor"
rsync -a \
    --exclude '.git/' \
    --exclude 'artifacts/' \
    --exclude 'vendor/' \
    --exclude '.phpunit.cache/' \
    --exclude 'tests/results/' \
    "$project_root/" "$plugin_root/"
cp "$project_root/scripts/release-autoload.php" "$plugin_root/vendor/autoload.php"

zip_path="$artifacts/ConferenceDiscountEligibility-$version.zip"
checksum_path="$artifacts/ConferenceDiscountEligibility-$version.sha256"
zip_name="$(basename "$zip_path")"
checksum_name="$(basename "$checksum_path")"
rm -f "$zip_path" "$checksum_path"
(cd "$staging" && zip -q -r "$zip_path" ConferenceDiscountEligibility)
(cd "$artifacts" && shasum -a 256 "$zip_name" > "$checksum_name")

php "$project_root/scripts/validate-package.php" "$zip_path"
unzip -tq "$zip_path"
(cd "$artifacts" && shasum -a 256 -c "$checksum_name")

echo "$zip_path"
echo "$checksum_path"
