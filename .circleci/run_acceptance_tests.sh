#!/bin/bash

# Runs the acceptance suite for the test files `circleci tests run` sends on stdin.
# It lives in a script rather than inline in config.yml because CircleCI has to
# launch the tests itself for "Rerun failed tests" to work.

set -eo pipefail

GROUP_FILE=tests/acceptance/_groups/circleci_split_group

# `circleci tests run` sends the names space separated on one line, and Codeception
# reads one path per line, so put each on its own. Blank lines would make it run with
# an empty group.
tr '[:space:]' '\n' | sed '/^[[:space:]]*$/d' > "$GROUP_FILE"

if [ ! -s "$GROUP_FILE" ]; then
  echo "No acceptance tests were assigned to this container."
  exit 0
fi

cat "$GROUP_FILE"

cd ../tests_env/docker

args=(
  --steps
  --debug
  -vvv
  --html
  --xml
  -g circleci_split_group
)

# -T and </dev/null because our stdin is the pipe `circleci tests run` sent the test
# list on, and compose refuses to allocate a TTY on it.
docker compose run -T -e SKIP_DEPS=1 \
  -e CIRCLE_BRANCH="${CIRCLE_BRANCH}" \
  -e CIRCLE_JOB="${CIRCLE_JOB}" \
  -e GH_TOKEN="${WP_GITHUB_WOOCOMMERCE_TOKEN}" \
  -e MULTISITE="${MULTISITE}" \
  -e BLOCKBASED_THEME="${BLOCKBASED_THEME}" \
  -e ENABLE_HPOS="${ENABLE_HPOS}" \
  -e ENABLE_HPOS_SYNC="${ENABLE_HPOS_SYNC}" \
  -e DISABLE_HPOS="${DISABLE_HPOS}" \
  -e WORDPRESS_VERSION="${WORDPRESS_VERSION}" \
  -e GUTENBERG_VERSION="${GUTENBERG_VERSION}" \
  codeception_acceptance "${args[@]}" < /dev/null
