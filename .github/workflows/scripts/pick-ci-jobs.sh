#!/usr/bin/env bash
# Picks the Checks jobs a pull request needs, by the rules in the config.
# Reads the changed files from stdin, one per line, and prints
# "<job>=true" or "<job>=false" for each job, for $GITHUB_OUTPUT. The
# reasons go to stderr.
#
# Usage: git diff --name-only HEAD^1 HEAD | pick-ci-jobs.sh .github/ci-jobs.yml
# Needs yq (mikefarah/yq), which comes with the GitHub runner.
# Tests: pick-ci-jobs.test.sh

set -euo pipefail

config=$1

fail() {
  echo "::error::$config: $*" >&2
  exit 1
}

# Prints the patterns of an area that the yq filter selects as one extended
# regex, which matches whole paths.
area_regex() { # <area> <yq filter>
  yq ".areas.\"$1\"[] | select($2)" "$config" \
    | sed -E -e 's/^!//' -e 's/[].[^$+(){}|\\?]/\\&/g' \
      -e 's#\*\*/#@@1@@#g' -e 's#\*\*#@@2@@#g' -e 's#\*#[^/]*#g' \
      -e 's#@@1@@#(.*/)?#g' -e 's#@@2@@#.*#g' -e 's#.*#^&$#' \
    | paste -sd '|' -
}

# Read all of stdin first, so the writer never gets a broken pipe.
changed=$(grep -v '^$' || true)

full_run=""
if [[ "${GITHUB_EVENT_NAME:-}" != pull_request ]]; then
  full_run="Every job runs on the ${GITHUB_EVENT_NAME:-unknown} event."
elif [[ "${GITHUB_BASE_REF:-}" == release ]]; then
  full_run="Every job runs for pull requests into release."
elif [[ -z "$changed" ]]; then
  full_run="No changed files, so every job runs."
fi

areas=$(yq '.areas | keys | .[]' "$config")
changed_areas=()
in_an_area=""
for area in $areas; do
  include=$(area_regex "$area" 'test("^!") | not')
  exclude=$(area_regex "$area" 'test("^!")')
  [[ -n "$include" ]] || fail "area $area has no patterns"
  matches=$(grep -E "$include" <<< "$changed" || true)
  if [[ -n "$exclude" && -n "$matches" ]]; then
    matches=$(grep -vE "$exclude" <<< "$matches" || true)
  fi
  if [[ -n "$matches" ]]; then
    changed_areas+=("$area")
    in_an_area+="$matches"$'\n'
  fi
done

in_no_area=$(grep -vxF -f <(printf '%s' "$in_an_area") <<< "$changed" || true)

skipped=()
for job in $(yq '.jobs | keys | .[]' "$config"); do
  [[ $(yq ".jobs.\"$job\" | type" "$config") == '!!seq' ]] || fail "job $job needs a list of areas"
  run=false
  for area in $(yq ".jobs.\"$job\"[]" "$config"); do
    grep -qxF "$area" <<< "$areas" || fail "job $job names an unknown area: $area"
    if [[ " ${changed_areas[*]-} " == *" $area "* ]]; then
      run=true
    fi
  done
  if [[ -n "$full_run" || -n "$in_no_area" ]]; then
    run=true
  fi
  [[ "$run" == true ]] || skipped+=("$job")
  echo "$job=$run"
done

if [[ -n "$full_run" ]]; then
  echo "$full_run" >&2
elif [[ -n "$in_no_area" ]]; then
  echo "Every job runs, because these files are in no area of $config:" >&2
  echo >&2
  # head before sed: head exits early, which would break sed's pipe
  head -20 <<< "$in_no_area" | sed 's/^/- /' >&2
  count=$(wc -l <<< "$in_no_area")
  if (( count > 20 )); then
    echo "- and $(( count - 20 )) more" >&2
  fi
else
  echo "Changed areas: ${changed_areas[*]}" >&2
  echo >&2
  echo "Skipped jobs: ${skipped[*]:-none}" >&2
fi
