#!/usr/bin/env bash
# Tests pick-ci-jobs.sh with the real config and with small test configs,
# and checks that checks.yml uses every job of the config.
# Needs yq. Run: bash .github/workflows/scripts/pick-ci-jobs.test.sh

set -uo pipefail

dir="$(cd "$(dirname "$0")" && pwd)"
script="$dir/pick-ci-jobs.sh"
config="$dir/../../ci-jobs.yml"
checks="$dir/../checks.yml"
tmp=$(mktemp -d)
trap 'rm -f "$tmp"/*; rmdir "$tmp"' EXIT
failures=0
all=$(yq '.jobs | keys | join(" ")' "$config")

# Prints the jobs that run, in config order. Set cfg to use another config.
runs() { # <event> <base branch> [changed files...]
  local event=$1 base=$2
  shift 2
  printf '%s\n' "$@" \
    | GITHUB_EVENT_NAME=$event GITHUB_BASE_REF=$base bash "$script" "${cfg:-$config}" 2> "$tmp/err" \
    | sed -n 's/=true$//p' | paste -sd ' ' -
}
pr() { runs pull_request trunk "$@"; }

expect() { # <description> <expected> <actual>
  if [[ "$2" != "$3" ]]; then
    echo "FAIL: $1: expected [$2], got [$3]"; failures=$((failures + 1))
  fi
}
expect_err() { # <description> <fragment>
  if ! grep -qF -- "$2" "$tmp/err"; then
    echo "FAIL: $1: [$2] not in [$(cat "$tmp/err")]"; failures=$((failures + 1))
  fi
}
expect_failure() { # <description> <error fragment> <config>
  if printf 'a/1.txt\n' | GITHUB_EVENT_NAME=pull_request bash "$script" "$3" > /dev/null 2> "$tmp/err"; then
    echo "FAIL: $1: the script passed"; failures=$((failures + 1))
  fi
  expect_err "$1" "$2"
}

# The real config
expect 'docs' '' "$(pr AGENTS.md mailpoet/changelog/2026-01-01-x.md .ai/skills/code-quality/SKILL.md .github/ISSUE_TEMPLATE/bug.md .claude/settings.json)"
expect_err 'docs: areas' 'Changed areas: docs'
expect 'js' 'qa_js js_tests release_zip acceptance' \
  "$(pr mailpoet/assets/js/src/newsletters/listings.tsx mailpoet/assets/css/src/admin.scss packages/js/components/src/index.ts mailpoet/eslint.config.js mailpoet/changelog/x.md)"
expect_err 'js: areas' 'Changed areas: docs js'
expect_err 'js: skipped' 'Skipped jobs: static_analysis qa_php unit_tests integration security_analysis ci_script_tests'
expect 'JS tests' 'qa_js js_tests' "$(pr mailpoet/tests/javascript/ajax.spec.ts mailpoet/tests/javascript-newsletter-editor/x.js)"
expect 'unit tests' 'static_analysis qa_php unit_tests' "$(pr mailpoet/tests/unit/Config/EnvTest.php)"
expect 'integration tests' 'static_analysis qa_php integration' "$(pr mailpoet/tests/integration/Config/RendererTest.php)"
expect 'acceptance tests' 'static_analysis qa_php acceptance' "$(pr mailpoet/tests/acceptance/Forms/FormCest.php)"
expect 'js and integration tests' 'static_analysis qa_php qa_js js_tests release_zip integration acceptance' \
  "$(pr mailpoet/assets/js/src/x.ts mailpoet/tests/integration/x.php)"

for file in \
  mailpoet/assets/js/src/mailpoet-custom-email-editor-blocks/latest-posts/block.json \
  mailpoet/assets/js/src/marketing-optin-block/index.tsx \
  mailpoet/lib/Config/Env.php \
  mailpoet/views/layout.html \
  mailpoet/RoboFile.php \
  mailpoet/tests/_support/AcceptanceTester.php \
  mailpoet/tests/_data/x.json \
  mailpoet/tests/DataFactories/Newsletter.php \
  mailpoet/webpack.config.js \
  tools/webpack/webpack-emit-all-plugin.js \
  package.json \
  mailpoet/package.json \
  pnpm-lock.yaml \
  .nvmrc \
  mailpoet/readme.txt \
  mailpoet/.gitignore \
  mailpoet/assets/img/logo.png \
  mailpoet/plugin_repository/assets/banner.png \
  tests_env/docker/docker-compose.yml \
  .github/ci-jobs.yml \
  .github/workflows/checks.yml \
  .github/workflows/scripts/pick-ci-jobs.sh; do
  expect "$file runs every job" "$all" "$(pr README.md "$file")"
  expect_err "$file is listed" "- $file"
done
mapfile -t many < <(seq -f 'mailpoet/lib/F%g.php' 5000)
expect 'many files in no area' "$all" "$(pr "${many[@]}")"
expect_err 'many files: count' '- and 4980 more'

# Full runs
expect 'push' "$all" "$(runs push '' README.md)"
expect_err 'push: reason' 'Every job runs on the push event.'
expect 'manual run' "$all" "$(runs workflow_dispatch '' README.md)"
expect 'pull request into release' "$all" "$(runs pull_request release README.md)"
expect_err 'pull request into release: reason' 'Every job runs for pull requests into release.'
expect 'no changed files' "$all" "$(pr)"

# Patterns
cat > "$tmp/patterns.yml" <<'YAML'
areas:
  a:
    - 'a/*.txt'
    - '**/*.md'
    - 'x.y'
    - '!a/skip.txt'
jobs:
  j: [a]
  k: []
YAML
cfg="$tmp/patterns.yml"
expect '* in a folder' 'j' "$(pr a/1.txt)"
expect '* does not cross folders' 'j k' "$(pr a/b/1.txt)"
expect '**/ matches no folder' 'j' "$(pr README.md)"
expect '**/ matches folders' 'j' "$(pr d/e/f.md)"
expect '. is a dot' 'j k' "$(pr xzy)"
expect '! takes a file out' 'j k' "$(pr a/skip.txt)"
unset cfg

# Config errors
printf 'areas:\n  a: [a/*]\njobs:\n  j: [b]\n' > "$tmp/unknown-area.yml"
expect_failure 'unknown area' 'job j names an unknown area: b' "$tmp/unknown-area.yml"
printf 'areas:\n  a: [a/*]\njobs:\n  j: a\n' > "$tmp/not-a-list.yml"
expect_failure 'job without a list' 'job j needs a list of areas' "$tmp/not-a-list.yml"
printf 'areas:\n  a: ["!a/*"]\njobs:\n  j: [a]\n' > "$tmp/only-exclusions.yml"
expect_failure 'area without patterns' 'area a has no patterns' "$tmp/only-exclusions.yml"

# checks.yml
sorted() { tr ' ' '\n' | sort | paste -sd ' ' -; }
expect 'changes outputs are the config jobs' "$(sorted <<< "$all")" \
  "$(yq '.jobs.changes.outputs | keys | join(" ")' "$checks" | sorted)"
expect 'jobs read only outputs from the config' "$(sorted <<< "$all")" \
  "$(grep -o 'needs\.changes\.outputs\.[a-z_]*' "$checks" | sed 's/.*\.//' | sort -u | paste -sd ' ' -)"
expect 'All checks needs every job' \
  "$(yq '.jobs | keys | .[] | select(. != "all_checks" and . != "notify")' "$checks" | sort | paste -sd ' ' -)" \
  "$(yq '.jobs.all_checks.needs[]' "$checks" | sort | paste -sd ' ' -)"

if [[ $failures -gt 0 ]]; then
  echo "$failures failed"
  exit 1
fi
echo 'All passed.'
