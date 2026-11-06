#!/usr/bin/env bash
# Tests trunk-failure-alert.sh with fake gh, curl and sleep commands.
# Run: bash .github/workflows/scripts/trunk-failure-alert.test.sh

set -uo pipefail

script="$(cd "$(dirname "$0")" && pwd)/trunk-failure-alert.sh"
tmp=$(mktemp -d)
trap 'find "$tmp" -type f -delete; rmdir "$tmp/bin" "$tmp"' EXIT
mkdir "$tmp/bin"
failures=0

cat > "$tmp/bin/gh" <<'FAKE'
#!/usr/bin/env bash
echo "gh $*" >> "$TMP/gh.log"
if [[ "$1 $2" == "run rerun" ]]; then
  [[ -f "$TMP/rerun-refused" ]] && exit 1
  [[ -f "$TMP/run-after.json" ]] && cp "$TMP/run-after.json" "$TMP/run.json"
  exit 0
fi
if [[ -f "$TMP/gh-fails-once" && "$2" == repos/o/r/actions/runs/1 && -f "$TMP/slept" ]]; then
  rm "$TMP/gh-fails-once"
  exit 1
fi
case "$2" in
  repos/o/r/actions/runs/1/jobs*) cat "$TMP/jobs.json" ;;
  repos/o/r/actions/runs/1) cat "$TMP/run.json" ;;
  repos/o/r/actions/runs\?*) cat "$TMP/runs.json" ;;
  repos/o/r/commits/*/pulls) cat "$TMP/pulls.json" ;;
  repos/o/r/pulls/7) cat "$TMP/pull.json" ;;
  *) echo "unexpected gh call: $*" >&2; exit 1 ;;
esac
FAKE
cat > "$tmp/bin/curl" <<'FAKE'
#!/usr/bin/env bash
if [[ -f "$TMP/slack-refused" ]]; then
  echo '{"ok":false,"error":"channel_not_found"}'
  exit 0
fi
cat >> "$TMP/posts.json"
echo '{"ok":true}'
FAKE
printf '#!/usr/bin/env bash\n' > "$tmp/bin/sleep"
chmod +x "$tmp/bin/gh" "$tmp/bin/curl" "$tmp/bin/sleep"

# run_json <id> <attempt> <status> <conclusion> <triggering actor> [sha] [updated_at]
run_json() {
  jq -n --argjson id "$1" --argjson attempt "$2" --arg status "$3" --arg conclusion "$4" \
    --arg trigger "$5" --arg sha "${6:-abc}" --arg updated "${7:-2020-01-01T00:00:00Z}" '{
      id: $id, name: "Checks", run_attempt: $attempt, status: $status, conclusion: $conclusion,
      head_branch: "trunk", head_sha: $sha, updated_at: $updated,
      html_url: "https://example.test/run/\($id)",
      actor: {login: "pusher"},
      triggering_actor: {login: $trigger}
    }'
}

set_up() {
  user_map='{"merger":"U1"}'
  expected_status=0
  rm -f "$tmp"/*.json "$tmp"/*.log "$tmp/rerun-refused" "$tmp/slack-refused" "$tmp/gh-fails-once" "$tmp/slept"
  run_json 1 1 completed failure pusher > "$tmp/run.json"
  run_json 1 2 completed failure 'github-actions[bot]' > "$tmp/run-after.json"
  run_json 1 1 completed failure pusher | jq '{workflow_runs: [.]}' > "$tmp/runs.json"
  echo '{"jobs":[{"name":"PHPStan","conclusion":"failure"},{"name":"All checks","conclusion":"failure"},{"name":"Lint","conclusion":"success"}]}' > "$tmp/jobs.json"
  echo '[{"number":7,"merged_at":"2020-01-01T00:00:00Z"}]' > "$tmp/pulls.json"
  echo '{"number":7,"title":"Fix <a> & b","html_url":"https://example.test/pull/7","merged_by":{"login":"Merger"}}' > "$tmp/pull.json"
}

run_script() {
  TMP="$tmp" PATH="$tmp/bin:$PATH" GH_TOKEN=x SLACK_BOT_TOKEN=x REPO=o/r CHANNEL=C1 \
    SLACK_USER_MAP="$user_map" WORKFLOWS=$'Checks' RUN_ID="$1" bash "$script" > "$tmp/out.log" 2>&1
  status=$?
  if [[ $status -ne $expected_status ]]; then
    echo "FAIL: script exited with $status, expected $expected_status"; cat "$tmp/out.log"; failures=$((failures + 1))
  fi
}

posts() { [[ -f "$tmp/posts.json" ]] && jq -s 'length' "$tmp/posts.json" || echo 0; }
text() { jq -r '.text' "$tmp/posts.json"; }

expect() { # <description> <expected> <actual>
  if [[ "$2" != "$3" ]]; then
    echo "FAIL: $1: expected [$2], got [$3]"; failures=$((failures + 1))
  fi
}
expect_text() { # <description> <fragment>
  if [[ "$(text 2>/dev/null)" != *"$2"* ]]; then
    echo "FAIL: $1: [$2] not in [$(text 2>/dev/null)]"; failures=$((failures + 1))
  fi
}
expect_no_text() { # <description> <fragment>
  if [[ "$(text 2>/dev/null)" == *"$2"* ]]; then
    echo "FAIL: $1: [$2] found in [$(text 2>/dev/null)]"; failures=$((failures + 1))
  fi
}
rerun_calls() { grep -c 'run rerun 1 -R o/r --failed' "$tmp/gh.log"; }

# A failure that passes on the retry posts nothing.
set_up
run_json 1 2 completed success 'github-actions[bot]' > "$tmp/run-after.json"
run_script 1
expect 'passing retry: rerun' 1 "$(rerun_calls)"
expect 'passing retry: posts' 0 "$(posts)"

# A cancelled retry posts nothing.
set_up
run_json 1 2 completed cancelled 'github-actions[bot]' > "$tmp/run-after.json"
run_script 1
expect 'cancelled retry: posts' 0 "$(posts)"

# A failure that fails again posts one message with everything in it.
set_up
run_script 1
expect 'failing retry: rerun' 1 "$(rerun_calls)"
expect 'failing retry: posts' 1 "$(posts)"
expect_text 'mention' '<@U1>'
expect_text 'repo' 'o/r'
expect_text 'run link' '<https://example.test/run/1|Checks>'
expect_text 'job' 'PHPStan'
expect_no_text 'summary job left out' 'All checks'
expect_text 'PR link, escaped' '<https://example.test/pull/7|#7 Fix &lt;a&gt; &amp; b>'
expect_text 'says it retried' 'after a retry'
expect 'channel' C1 "$(jq -r '.channel' "$tmp/posts.json")"

# The retry waits for the new attempt to finish, reading it again until it has.
set_up
run_json 1 2 in_progress '' 'github-actions[bot]' > "$tmp/run-after.json"
cat > "$tmp/bin/sleep" <<'FAKE'
#!/usr/bin/env bash
if [[ -f "$TMP/slept" ]]; then
  jq '.status = "completed" | .conclusion = "failure"' "$TMP/run.json" > "$TMP/next.json"
  mv "$TMP/next.json" "$TMP/run.json"
fi
touch "$TMP/slept"
FAKE
run_script 1
expect 'waits for the retry: reads' 3 "$(grep -c 'api repos/o/r/actions/runs/1$' "$tmp/gh.log")"
expect 'waits for the retry: posts' 1 "$(posts)"

# One failed read while waiting doesn’t lose the post.
set_up
run_json 1 2 in_progress '' 'github-actions[bot]' > "$tmp/run-after.json"
touch "$tmp/gh-fails-once"
run_script 1
expect 'failed read: posts' 1 "$(posts)"
printf '#!/usr/bin/env bash\n' > "$tmp/bin/sleep"

# When the retry can’t start, it posts at once.
set_up
touch "$tmp/rerun-refused"
run_script 1
expect 'refused retry: posts' 1 "$(posts)"
expect_no_text 'refused retry: wording' 'after a retry'

# The event for the alert's own retry does nothing: the first job handles it.
set_up
run_json 1 2 completed failure 'github-actions[bot]' > "$tmp/run.json"
run_script 1
expect 'own retry: rerun' 0 "$(rerun_calls)"
expect 'own retry: posts' 0 "$(posts)"

# Someone re-running by hand gets no second retry, but a failure still posts.
set_up
run_json 1 2 completed failure someone > "$tmp/run.json"
run_script 1
expect 'manual re-run: rerun' 0 "$(rerun_calls)"
expect 'manual re-run: posts' 1 "$(posts)"

# Slack refusing the message fails the job and says why.
set_up
touch "$tmp/slack-refused"
expected_status=1
run_script 1
grep -q 'channel_not_found' "$tmp/out.log"
expect 'refused post: reason logged' 0 $?

# A long list of failing jobs stays short.
set_up
jq -n '{jobs: [range(7) | {name: "Job\(.)", conclusion: "failure"}]}' > "$tmp/jobs.json"
run_script 1
expect_text 'capped jobs: count' 'and 2 more'
expect_no_text 'capped jobs: list' 'Job5'

# A PR that was closed without merging is not the cause.
set_up
echo '[{"number":99,"merged_at":null},{"number":7,"merged_at":"2020-01-01T00:00:00Z"}]' > "$tmp/pulls.json"
run_script 1
expect_text 'unmerged PR skipped' '#7 '

# A bot merge posts without a mention.
set_up
echo '{"number":7,"title":"Bump","html_url":"https://example.test/pull/7","merged_by":{"login":"github-actions[bot]"}}' > "$tmp/pull.json"
run_script 1
expect 'bot merge: posts' 1 "$(posts)"
expect_no_text 'bot merge: mention' '<@'
expect_text 'bot merge: name' 'github-actions[bot]'

# Someone not in the map gets their plain name.
set_up
echo '{"number":7,"title":"T","html_url":"https://example.test/pull/7","merged_by":{"login":"Stranger"}}' > "$tmp/pull.json"
run_script 1
expect_no_text 'unmapped: mention' '<@'
expect_text 'unmapped: name' 'Stranger'

# A broken map still posts, with the plain name.
set_up
user_map='not json'
run_script 1
expect 'broken map: posts' 1 "$(posts)"
expect_text 'broken map: name' 'Merger'

# A push without a PR names the person who pushed and links the commit.
set_up
echo '[]' > "$tmp/pulls.json"
run_script 1
expect_text 'no PR: pusher' 'pusher'
expect_text 'no PR: commit' 'abc'

# The first failure since the last success is the one that broke trunk.
set_up
jq -n --argjson new "$(run_json 1 1 completed failure pusher new)" \
  --argjson old "$(run_json 0 1 completed failure pusher culprit)" \
  --argjson good "$(run_json -1 1 completed success pusher good)" \
  --argjson older "$(run_json -2 1 completed failure pusher ancient)" \
  '{workflow_runs: [$new, $old, $good, $older]}' > "$tmp/runs.json"
run_script 1
grep -q 'commits/culprit/pulls' "$tmp/gh.log"
expect 'blames the first failure' 0 $?

# A cancelled run says nothing about whether trunk was fixed.
set_up
jq -n --argjson new "$(run_json 1 1 completed failure pusher new)" \
  --argjson cancelled "$(run_json 0 1 completed cancelled pusher skipped)" \
  --argjson old "$(run_json -1 1 completed failure pusher culprit)" \
  '{workflow_runs: [$new, $cancelled, $old]}' > "$tmp/runs.json"
run_script 1
grep -q 'commits/culprit/pulls' "$tmp/gh.log"
expect 'skips a cancelled run' 0 $?

# With no run history to read, the failed run itself is the cause.
set_up
echo '{"workflow_runs":[]}' > "$tmp/runs.json"
run_script 1
grep -q 'commits/abc/pulls' "$tmp/gh.log"
expect 'no history: uses the failed run' 0 $?

# Hourly: a trunk still red after the wait pings again and doesn’t retry.
set_up
run_script ''
expect 'still red: rerun' 0 "$(grep -c 'run rerun' "$tmp/gh.log")"
expect 'still red: posts' 1 "$(posts)"
expect_text 'still red: wording' 'still red'
expect_text 'still red: mention' '<@U1>'

# Hourly: green stops it.
set_up
run_json 1 1 completed success pusher | jq '{workflow_runs: [.]}' > "$tmp/runs.json"
run_script ''
expect 'green: posts' 0 "$(posts)"

# Hourly: a run in progress might fix it, so wait.
set_up
jq -n --argjson running "$(run_json 2 1 in_progress '' pusher new)" \
  --argjson red "$(run_json 1 1 completed failure pusher)" \
  '{workflow_runs: [$running, $red]}' > "$tmp/runs.json"
run_script ''
expect 'in progress: posts' 0 "$(posts)"

# Hourly: a failure from less than an hour ago has just been posted.
set_up
run_json 1 2 completed failure pusher abc "$(jq -nr 'now | todate')" | jq '{workflow_runs: [.]}' > "$tmp/runs.json"
run_script ''
expect 'fresh failure: posts' 0 "$(posts)"

# Hourly: workflows that aren’t watched don’t count.
set_up
run_json 1 1 completed failure pusher | jq '.name = "CodeQL" | {workflow_runs: [.]}' > "$tmp/runs.json"
run_script ''
expect 'other workflow: posts' 0 "$(posts)"

if [[ $failures -gt 0 ]]; then
  echo "$failures failed"
  exit 1
fi
echo 'All passed.'
