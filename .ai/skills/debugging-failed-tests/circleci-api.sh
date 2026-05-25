#!/usr/bin/env bash
# Authenticated curl wrapper for the CircleCI API.
#
# Reads the CircleCI API token from (in order):
#   1. $CIRCLECI_TOKEN environment variable
#   2. WP_CIRCLECI_TOKEN in <repo>/mailpoet/.env
#
# The token is passed to curl via --config on stdin so it never appears in
# process argv, shell history, or transcript output.
#
# Usage (run from the repo root):
#   .ai/skills/debugging-failed-tests/circleci-api.sh <path-or-url> [extra curl args...]
#
# Examples:
#   .ai/skills/debugging-failed-tests/circleci-api.sh /api/v2/project/gh/mailpoet/mailpoet/job/12345
#   .ai/skills/debugging-failed-tests/circleci-api.sh https://output.circle-artifacts.com/.../shot.png -o shot.png
#
# A first argument starting with "/" is appended to https://circleci.com;
# anything else must be a full http(s) URL (artifact hosts work too).

set -euo pipefail

die() { printf 'circleci-api.sh: %s\n' "$*" >&2; exit 1; }

[ $# -ge 1 ] || die "missing URL or path argument (see header comment for usage)"

target=$1
shift

case "$target" in
  http://*|https://*) url=$target ;;
  /*)                  url="https://circleci.com$target" ;;
  *) die "first argument must be a full http(s) URL or a path starting with '/'" ;;
esac

repo_root=$(cd "$(dirname "$0")/../../.." && pwd)
env_file="$repo_root/mailpoet/.env"

token=${CIRCLECI_TOKEN:-}
if [ -z "$token" ] && [ -r "$env_file" ]; then
  line=$(grep -E '^[[:space:]]*WP_CIRCLECI_TOKEN=' "$env_file" | head -n1 || true)
  if [ -n "$line" ]; then
    val=${line#*=}
    val=${val%$'\r'}
    val=${val#\"}; val=${val%\"}
    val=${val#\'}; val=${val%\'}
    token=$val
  fi
fi
token=${token%$'\n'}

[ -n "$token" ] || die "no CircleCI token found. Set one of:
  - CIRCLECI_TOKEN in your shell environment, or
  - WP_CIRCLECI_TOKEN=<your-token> in $env_file
Get a personal API token at https://app.circleci.com/settings/user/tokens"

# Pass the token via curl's --config on stdin (here-string). Bash sets up the
# redirection inside the current process — the token never enters argv of
# curl or any child process, so it cannot leak through 'ps' or shell tracing
# of curl itself.
exec curl --fail-with-body --silent --show-error \
  --config /dev/stdin \
  "$@" "$url" <<< "header = \"Circle-Token: $token\""
