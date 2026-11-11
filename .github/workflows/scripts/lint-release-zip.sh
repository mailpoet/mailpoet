#!/usr/bin/env bash
# Lint every PHP file in the release zip with the PHP on PATH.
# Usage: lint-release-zip.sh <mailpoet.zip> <exclude-file>
set -euo pipefail

zip_file=$(realpath "${1:?Usage: $0 <zip> <exclude-file>}")
exclude_file=$(realpath "${2:?Usage: $0 <zip> <exclude-file>}")

work_dir=$(mktemp -d)
trap 'rm -rf "$work_dir"' EXIT
unzip -q "$zip_file" -d "$work_dir"
cd "$work_dir/mailpoet"

php -v

excludes=$(grep -v '^[[:space:]]*\(#\|$\)' "$exclude_file" || true)

status=0
stale=""
while IFS= read -r path; do
  [ -z "$path" ] && continue
  if [ ! -f "$path" ]; then
    stale="$stale$path"$'\n'
  fi
done <<< "$excludes"
if [ -n "$stale" ]; then
  echo "ERROR: these paths in $(basename "$exclude_file") are not in the zip any more. Remove them:"
  printf '%s' "$stale" | sed 's/^/  /'
  status=1
fi

# `php -l` status is swallowed so that xargs does not abort on exit code 255.
failed=$(find . -name '*.php' -print0 | sort -z | xargs -0 -P4 -I{} sh -c 'php -l "$1" > /dev/null 2>&1 || echo "$1"' _ {} | sed 's|^\./||' | sort)

unexpected=$(comm -23 <(printf '%s\n' "$failed" | sed '/^$/d') <(printf '%s\n' "$excludes" | sed '/^$/d' | sort) || true)
if [ -n "$unexpected" ]; then
  echo "ERROR: these files do not parse on this PHP version:"
  printf '%s\n' "$unexpected" | sed 's/^/  /'
  status=1
fi

if [ "$status" -eq 0 ]; then
  echo "OK: all PHP files in the zip parse. Excluded: $(printf '%s\n' "$excludes" | sed '/^$/d' | wc -l | tr -d ' ')."
fi
exit "$status"
