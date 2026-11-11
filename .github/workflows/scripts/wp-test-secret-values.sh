#!/usr/bin/env bash
# Reads the WP_TEST_ENV secret (the WP_TEST_* lines CircleCI has as project
# variables).
#   --lines  prints the WP_TEST_*=value lines, to export them.
#   default  prints the secret values, one per line, to mask them in the log
#            and remove them from uploaded test results. Flags and numbers are
#            left out, as they would hide every “true” in the log. The forms a
#            value can take in test output are printed too: JSON-escaped, and
#            for the MailPoet API key the base64 Basic auth the Bridge sends.

set -euo pipefail

mode=${1:-values}

while IFS= read -r line || [[ -n "$line" ]]; do
  line=${line%$'\r'}
  [[ "$line" == WP_TEST_*=* ]] || continue
  if [[ "$mode" == --lines ]]; then
    printf '%s\n' "$line"
    continue
  fi
  key=${line%%=*}
  value=${line#*=}
  [[ -z "$value" || "$value" =~ ^(true|false|[0-9]+)$ ]] && continue
  printf '%s\n' "$value"
  if [[ "$value" == */* ]]; then
    printf '%s\n' "${value//\//\\/}"
  fi
  if [[ "$key" == WP_TEST_MAILER_MAILPOET_API ]]; then
    printf 'api:%s' "$value" | base64 | tr -d '\n'
    printf '\n'
  fi
done <<< "${WP_TEST_ENV:-}"
