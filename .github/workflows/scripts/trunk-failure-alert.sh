#!/usr/bin/env bash
# Posts a failed trunk build to Slack and mentions the person who merged it.
#   RUN_ID set    retries the failed run once, and posts if it fails again.
#   RUN_ID empty  posts again for each workflow in WORKFLOWS (one per line) that
#                 has been red on trunk for about an hour.
# Tests: trunk-failure-alert.test.sh

set -euo pipefail

retry_actor='github-actions[bot]'
still_red_after_seconds=3300

# The runs of one workflow on one branch, newest first.
branch_runs() {
  gh api "repos/$REPO/actions/runs?branch=$2&event=push&per_page=100" |
    jq --arg name "$1" '[.workflow_runs[] | select(.name == $name)]'
}

slack_escape() {
  jq -Rr 'gsub("&"; "&amp;") | gsub("<"; "&lt;") | gsub(">"; "&gt;")' <<<"$1"
}

# A real mention for a person in the map, the plain GitHub name for anyone else.
# Bots are never in the map.
person() {
  local slack_id
  slack_id=$(jq -r --arg login "$1" '.[$login | ascii_downcase] // empty' <<<"${SLACK_USER_MAP:-"{}"}" 2>/dev/null)
  if [[ -n "$slack_id" ]]; then
    printf '<@%s>' "$slack_id"
  else
    printf '%s' "$1"
  fi
}

# “<PR link>, merged by <person>” for the first failure since the last success,
# as later failures in the row are usually the same breakage.
cause() {
  local run=$1 name branch culprit sha pr_number pull
  name=$(jq -r '.name' <<<"$run")
  branch=$(jq -r '.head_branch' <<<"$run")
  culprit=$(branch_runs "$name" "$branch" | jq --argjson fallback "$run" '
    map(select(.id <= $fallback.id and (.conclusion == "success" or .conclusion == "failure"))) |
    (map(.conclusion) | index("success")) as $last_good |
    (.[:$last_good] | last) // $fallback')
  sha=$(jq -r '.head_sha' <<<"$culprit")
  pr_number=$(gh api "repos/$REPO/commits/$sha/pulls" | jq -r '[.[] | select(.merged_at)][0].number // empty') || pr_number=''
  if [[ -n "$pr_number" ]] && pull=$(gh api "repos/$REPO/pulls/$pr_number"); then
    printf '<%s|#%s %s>, merged by %s' \
      "$(jq -r '.html_url' <<<"$pull")" "$pr_number" "$(slack_escape "$(jq -r '.title' <<<"$pull")")" \
      "$(person "$(jq -r '.merged_by.login' <<<"$pull")")"
  else
    printf '<https://github.com/%s/commit/%s|%s>, pushed by %s' "$REPO" "$sha" "${sha:0:8}" \
      "$(person "$(jq -r '.actor.login' <<<"$culprit")")"
  fi
}

failing_jobs() {
  gh api "repos/$REPO/actions/runs/$1/jobs?per_page=100" | jq -r '
    [.jobs[] | select(.conclusion != "success" and .conclusion != "skipped" and .name != "All checks") | .name] |
    if length > 5 then (.[:5] | join(", ")) + ", and \(length - 5) more" else join(", ") end'
}

post() {
  local response
  response=$(jq -n --arg channel "$CHANNEL" --arg text "$1" '{channel: $channel, text: $text, unfurl_links: false}' |
    curl -sS -X POST https://slack.com/api/chat.postMessage \
      -H "Authorization: Bearer $SLACK_BOT_TOKEN" \
      -H 'Content-Type: application/json; charset=utf-8' \
      --data @-)
  if ! jq -e '.ok' <<<"$response" >/dev/null; then
    echo "Slack refused the message: $(jq -r '.error // "unknown error"' <<<"$response")" >&2
    return 1
  fi
}

message() {
  local run=$1 headline=$2 name url id
  name=$(jq -r '.name' <<<"$run")
  url=$(jq -r '.html_url' <<<"$run")
  id=$(jq -r '.id' <<<"$run")
  printf ':red_circle: *%s*: `%s` %s: <%s|%s>\nFailing: %s\nCaused by: %s' \
    "$REPO" "$(jq -r '.head_branch' <<<"$run")" "$headline" "$url" "$name" \
    "$(failing_jobs "$id")" "$(cause "$run")"
}

alert() {
  local run attempt
  run=$(gh api "repos/$REPO/actions/runs/$RUN_ID")
  attempt=$(jq -r '.run_attempt' <<<"$run")

  # The job that started this retry is still waiting for it, and will post.
  if [[ "$attempt" -gt 1 && "$(jq -r '.triggering_actor.login' <<<"$run")" == "$retry_actor" ]]; then
    return
  fi

  if [[ "$attempt" -eq 1 ]] && gh run rerun "$RUN_ID" -R "$REPO" --failed; then
    while :; do
      sleep 60
      run=$(gh api "repos/$REPO/actions/runs/$RUN_ID") || continue
      if [[ "$(jq -r '.run_attempt' <<<"$run")" -gt 1 && "$(jq -r '.status' <<<"$run")" == 'completed' ]]; then
        break
      fi
    done
    if [[ "$(jq -r '.conclusion' <<<"$run")" != 'failure' ]]; then
      return
    fi
  fi

  # A newer merge passed while the retry ran, so trunk is already green.
  if branch_runs "$(jq -r '.name' <<<"$run")" "$(jq -r '.head_branch' <<<"$run")" |
    jq -e --argjson id "$RUN_ID" 'any(.[]; .id > $id and .conclusion == "success")' >/dev/null; then
    return
  fi

  local headline='failed'
  if [[ "$(jq -r '.run_attempt' <<<"$run")" -gt 1 ]]; then
    headline='failed after a retry'
  fi
  post "$(message "$run" "$headline")"
}

still_red() {
  local name latest
  while IFS= read -r name <&3; do
    latest=$(branch_runs "$name" trunk | jq '.[0] // empty')
    if [[ -n "$latest" ]] && jq -e --argjson wait "$still_red_after_seconds" \
      '.conclusion == "failure" and (.updated_at | fromdate) < (now - $wait)' <<<"$latest" >/dev/null; then
      post "$(message "$latest" 'is still red')"
    fi
  done 3<<<"$WORKFLOWS"
}

if [[ -n "${RUN_ID:-}" ]]; then
  alert
else
  still_red
fi
