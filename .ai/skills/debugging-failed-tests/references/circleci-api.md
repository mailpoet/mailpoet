# CircleCI API — useful endpoints

All calls go through `.ai/skills/debugging-failed-tests/circleci-api.sh` from the repo root. The wrapper handles auth — do not read or pass tokens yourself. Pass a path starting with `/` (it expands to `https://circleci.com<path>`) or a full URL (for artifact downloads from `output.circle-artifacts.com`).

Output is JSON on stdout; pipe to `jq` for structured access.

## Identifying a job from a URL

CircleCI URLs come in two shapes:

- `https://app.circleci.com/pipelines/github/mailpoet/mailpoet/<pipeline-num>/workflows/<workflow-uuid>/jobs/<job-number>`
- `https://circleci.com/gh/mailpoet/mailpoet/<job-number>` (legacy)

The trailing integer is the **job number** — that's what every endpoint below wants.

## Job details — branch, SHA, status

```
.ai/skills/debugging-failed-tests/circleci-api.sh /api/v2/project/gh/mailpoet/mailpoet/job/<job-number>
```

Useful fields:

- `status` — `success`, `failed`, `running`, `infrastructure_fail`, `canceled`. An `infrastructure_fail` is CircleCI's problem; retry the job before debugging.
- `pipeline.id` — needed if you want to walk the workflow (see below).
- `pipeline.vcs.revision` — the commit SHA the job ran against.
- `pipeline.vcs.branch` — the branch the job ran against.
- `web_url` — the human URL of the job (handy to paste into a report).
- `parallel_runs` — Codeception parallel-shard count.

## Test failures (structured)

```
.ai/skills/debugging-failed-tests/circleci-api.sh /api/v2/project/gh/mailpoet/mailpoet/<job-number>/tests
```

One entry per test with `name`, `classname`, `result` (`success` / `failure` / `error` / `skipped`), `message`, `run_time`. Filter to `result != "success"` and you have your exact repro list — far cheaper than scrolling step logs.

```bash
.ai/skills/debugging-failed-tests/circleci-api.sh /api/v2/project/gh/mailpoet/mailpoet/<job-number>/tests \
  | jq '.items[] | select(.result != "success") | {classname, name, message}'
```

## Artifacts (screenshots, HTML dumps, browser logs)

List artifacts:

```
.ai/skills/debugging-failed-tests/circleci-api.sh /api/v2/project/gh/mailpoet/mailpoet/<job-number>/artifacts
```

Returns `{ items: [{ path, url, node_index }] }`. For acceptance failures, the `*.fail.png` and `*.fail.html` artifacts under `tests/_output/` are usually decisive.

Download an individual artifact (use the full URL from the listing):

```
.ai/skills/debugging-failed-tests/circleci-api.sh "<artifact-url-from-listing>" -o <local-name>
```

## Sibling jobs in the same workflow

When other parallel jobs failed alongside yours, the root cause is often shared (e.g. WC beta broke the whole matrix, a fixture changed and every shard tripped on it).

Walk pipeline → workflows → jobs:

```
.ai/skills/debugging-failed-tests/circleci-api.sh /api/v2/pipeline/<pipeline-id>/workflow
.ai/skills/debugging-failed-tests/circleci-api.sh /api/v2/workflow/<workflow-id>/job
```

## Test history (is this a long-running flake?)

```
.ai/skills/debugging-failed-tests/circleci-api.sh "/api/v2/insights/gh/mailpoet/mailpoet/workflows/<workflow-name>?branch=trunk"
```

Lists recent runs of the named workflow on a given branch with `status`, `duration`, `created_at`. Useful for distinguishing "first failure ever" from "been flaking for days", and for spotting the last known-good SHA to use as the floor of a `git bisect` or a `git diff <last-green>..<failing>`.

## Things the v2 API does NOT give you

- **WP / WC / PHP versions** used by the test container — these are printed in the raw step log by `tests_env/docker/codeception/docker-entrypoint.sh`, not surfaced as JSON fields. Easiest source: open the job's web URL and read the top of the failing step's output.
- **The CircleCI YAML config that ran** — if you need to understand the job matrix, read `.circleci/config.yml` at the failing SHA (`git show <sha>:.circleci/config.yml`).

## If the wrapper fails

If `.ai/skills/debugging-failed-tests/circleci-api.sh` exits non-zero saying the token is not configured, stop and report "CircleCI token not configured locally — see `.ai/skills/debugging-failed-tests/circleci-api.sh` for setup". Do not try to set up auth in the user's environment, do not paste paths or env-var names, do not guess.
