#!/bin/bash

# Runs one Codeception suite for the tests `circleci tests run` sends on stdin.
# It lives in a script rather than inline in config.yml because CircleCI has to
# launch the tests itself for the split and for “Rerun failed tests” to work.
# Usage: run_split_tests.sh <compose service>  (codeception_acceptance or codeception_integration)

set -eo pipefail

service=$1
GROUP_FILE=tests/acceptance/_groups/circleci_split_group

# `circleci tests run` sends the names space separated on one line, and Codeception
# reads one entry per line, so put each on its own. Blank lines would make it run with
# an empty group.
tr '[:space:]' '\n' | sed '/^[[:space:]]*$/d' > "$GROUP_FILE"

if [ ! -s "$GROUP_FILE" ]; then
  echo "No tests were assigned to this container."
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
if [[ -n "${SKIP_GROUP}" ]]; then
  args+=(--skip-group "${SKIP_GROUP}")
fi

# -T and </dev/null because our stdin is the pipe `circleci tests run` sent the test
# list on, and compose refuses to allocate a TTY on it.
docker compose run -T -e SKIP_DEPS=1 \
  -e CIRCLE_BRANCH="${CIRCLE_BRANCH}" \
  -e CIRCLE_JOB="${CIRCLE_JOB}" \
  -e GH_TOKEN="${WP_GITHUB_WOOCOMMERCE_TOKEN}" \
  -e SKIP_PLUGINS="${SKIP_PLUGINS}" \
  -e WP_TEST_MAILER_ENABLE_SENDING="${WP_TEST_MAILER_ENABLE_SENDING}" \
  -e WP_TEST_ENABLE_NETWORK_TESTS="${WP_TEST_ENABLE_NETWORK_TESTS}" \
  -e WP_TEST_MAILER_MAILPOET_API="${WP_TEST_MAILER_MAILPOET_API}" \
  -e WP_TEST_MAILER_SENDGRID_API="${WP_TEST_MAILER_SENDGRID_API}" \
  -e WP_TEST_MAILER_AMAZON_ACCESS="${WP_TEST_MAILER_AMAZON_ACCESS}" \
  -e WP_TEST_MAILER_AMAZON_REGION="${WP_TEST_MAILER_AMAZON_REGION}" \
  -e WP_TEST_MAILER_AMAZON_SECRET="${WP_TEST_MAILER_AMAZON_SECRET}" \
  -e WP_TEST_IMPORT_MAILCHIMP_API="${WP_TEST_IMPORT_MAILCHIMP_API}" \
  -e WP_TEST_IMPORT_MAILCHIMP_LISTS="${WP_TEST_IMPORT_MAILCHIMP_LISTS}" \
  -e WP_TEST_MAILER_SMTP_HOST="${WP_TEST_MAILER_SMTP_HOST}" \
  -e WP_TEST_MAILER_SMTP_LOGIN="${WP_TEST_MAILER_SMTP_LOGIN}" \
  -e WP_TEST_MAILER_SMTP_PASSWORD="${WP_TEST_MAILER_SMTP_PASSWORD}" \
  -e MULTISITE="${MULTISITE}" \
  -e BLOCKBASED_THEME="${BLOCKBASED_THEME}" \
  -e ENABLE_HPOS="${ENABLE_HPOS}" \
  -e ENABLE_HPOS_SYNC="${ENABLE_HPOS_SYNC}" \
  -e DISABLE_HPOS="${DISABLE_HPOS}" \
  -e WORDPRESS_VERSION="${WORDPRESS_VERSION}" \
  -e GUTENBERG_VERSION="${GUTENBERG_VERSION}" \
  "$service" "${args[@]}" < /dev/null
