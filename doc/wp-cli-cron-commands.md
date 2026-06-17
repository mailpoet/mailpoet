# MailPoet – WP-CLI cron commands

`wp mailpoet cron` is a set of [WP-CLI](https://wp-cli.org/) commands for inspecting and running
MailPoet's background (cron) tasks from the command line. They are aimed at developers and support
engineers debugging a site, and at operators who want to run MailPoet's queue from system cron
instead of (or alongside) the default web-request-driven runner.

Run `wp help mailpoet cron <command>` on any site for the same synopsis the commands below document —
the in-terminal help is generated from the command definitions and is always authoritative for the
exact options.

> In local `wp-env` development, prefix commands with `pnpm wp` (e.g. `pnpm wp mailpoet cron list`).

## Background: how MailPoet runs background work

MailPoet stores background work as rows in the `scheduled_tasks` table, each with a `type` (the worker
that handles it) and a `status`. A site-side runner (Action Scheduler by default, or WordPress cron)
periodically wakes a **daemon** that runs each worker over its due tasks. See
[`wp mailpoet cron types`](#wp-mailpoet-cron-types) for the list of worker types.

Task statuses:

| Status      | Meaning                                                                                                                                         |
| ----------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `scheduled` | Waiting to run at or after its scheduled time.                                                                                                  |
| `running`   | Being processed by the site daemon. Stored as `NULL` in the database; shown as `running`.                                                       |
| `cli`       | Claimed and being processed by a `wp mailpoet cron` process. Invisible to the site daemon (see [CLI execution](#cli-execution-the-cli-status)). |
| `completed` | Finished.                                                                                                                                       |
| `paused`    | On hold.                                                                                                                                        |
| `cancelled` | Cancelled.                                                                                                                                      |
| `invalid`   | Could not be processed.                                                                                                                         |

## Command reference

| Command                                      | Purpose                                             |
| -------------------------------------------- | --------------------------------------------------- |
| [`list`](#wp-mailpoet-cron-list)             | List scheduled tasks.                               |
| [`types`](#wp-mailpoet-cron-types)           | List known worker task types and their attributes.  |
| [`trigger`](#wp-mailpoet-cron-trigger)       | Mark a task due so the site's own cron picks it up. |
| [`run`](#wp-mailpoet-cron-run)               | Run a worker to completion inside the CLI process.  |
| [`run-daemon`](#wp-mailpoet-cron-run-daemon) | Run one full daemon pass inside the CLI process.    |
| [`add`](#wp-mailpoet-cron-add)               | Add a new task, optionally running it immediately.  |
| [`cancel`](#wp-mailpoet-cron-cancel)         | Cancel a scheduled, paused, or CLI task.            |

### `wp mailpoet cron list`

Lists scheduled tasks. By default shows only actionable tasks (`scheduled`, `running`, and `cli`),
newest first.

| Option                                  | Description                                                                                                                               |
| --------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `--status=<status>`                     | Filter by status: `scheduled`, `running`, `cli`, `completed`, `cancelled`, `paused`, `invalid`, or `all`. Defaults to the actionable set. |
| `--type=<type>`                         | Filter by task type.                                                                                                                      |
| `--limit=<n>`                           | Maximum number of tasks. Default 50.                                                                                                      |
| `--field=<field>` / `--fields=<fields>` | Output a single field / a comma-separated subset.                                                                                         |
| `--format=<format>`                     | `table` (default), `csv`, `json`, `ids`, `count`.                                                                                         |

Columns: `id`, `type`, `status`, `scheduled_at`, `priority`, `updated_at`.

```bash
wp mailpoet cron list
wp mailpoet cron list --status=running
wp mailpoet cron list --status=all --format=json
wp mailpoet cron list --type=sending --limit=10
```

### `wp mailpoet cron types`

Lists every known worker task type with its attributes: whether it is `addable` (see
[`add`](#wp-mailpoet-cron-add)), whether it `schedule_automatically`, whether it
`supports_multiple_instances`, and whether it is a `mailing` worker (the `sending` and
`stats_notification` jobs, which run their own mailer-driven flow instead of the standard worker
model). This is the authoritative list of valid `<type>` values for `trigger`, `run`, and `add`.

| Option                                  | Description                                        |
| --------------------------------------- | -------------------------------------------------- |
| `--field=<field>` / `--fields=<fields>` | Output a single field / a comma-separated subset.  |
| `--format=<format>`                     | `table` (default), `csv`, `json`, `yaml`, `count`. |

```bash
wp mailpoet cron types
wp mailpoet cron types --format=json
wp mailpoet cron types --fields=type,addable
```

### `wp mailpoet cron trigger`

Marks a task due **now** so the site's own cron processor picks it up. It does **not** kick the cron
pipeline itself — the MailPoet cron runner runs the task on its next tick. Use this when you want the
normal site runner to do the work; use [`run`](#wp-mailpoet-cron-run) to do it immediately in the CLI
process instead.

By type, it targets the soonest-due scheduled task of that type. `cli` tasks are rejected (a task an
active CLI process owns must not be yanked away).

| Argument / option | Description                                                                                                  |
| ----------------- | ------------------------------------------------------------------------------------------------------------ |
| `<type>`          | The task type to trigger.                                                                                    |
| `--task-id=<id>`  | Trigger an exact task by ID (also re-schedules a paused one) instead of the next scheduled task of the type. |

```bash
wp mailpoet cron trigger sending
wp mailpoet cron trigger bounce --task-id=42
```

### `wp mailpoet cron run`

Runs a worker **inside this WP-CLI process** (see [CLI execution](#cli-execution-the-cli-status)).

Without `--task-id`, it snapshots the currently-due tasks of the given type and runs each one once,
**atomically claiming each as `cli`** (a single guarded status update, hidden from the web daemon)
before processing. The claim is the safeguard against double-processing: a row another process already
took is skipped, so neither a concurrently running web daemon nor a second overlapping CLI run can pick
up the same task. A task that completes is marked `completed`; one that does not finish (or whose worker
fails) is handed back to the site cron. Self-rescheduling batched workers
(e.g. `subscribers_engagement_score`) process one batch per due task and schedule a continuation; that
continuation is created after the snapshot, so it is left for the site cron — run the command again, or
`trigger` it, to process the rest. Mailing workers run via their own process step; `run sending` runs
the Scheduler and then the SendingQueue.

With `--task-id`, it claims that one exact row (status `cli`, hidden from the web daemon) and runs
just that task.

The 20-second daemon execution limit is lifted by default so jobs run to completion; `--timeout`
restores a cap.

| Argument / option     | Description                                                                          |
| --------------------- | ------------------------------------------------------------------------------------ |
| `<type>`              | The task type to run.                                                                |
| `--task-id=<id>`      | Run this exact task in-process. Only `scheduled` or `paused` tasks can be run by ID. |
| `--timeout=<seconds>` | Cap the run at this many seconds. Omit to run to completion.                         |

```bash
wp mailpoet cron run log_cleanup
wp mailpoet cron run sending
wp mailpoet cron run bounce --task-id=42
wp mailpoet cron run sending --timeout=30
```

### `wp mailpoet cron run-daemon`

Runs one full daemon pass over **all** workers inside this WP-CLI process. Each worker runs once,
processing the tasks that are due. This is a single pass, not a backlog drain — a worker with many due
tasks may need several passes, or use [`run <type>`](#wp-mailpoet-cron-run) to drain one type.

Per-worker errors collected during the pass are printed and make the command exit non-zero.

| Option                | Description                                                                                                                                             |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `--timeout=<seconds>` | Cap the pass at this many seconds. Omit to run to completion. Unlike `run`, hitting `--timeout` mid-pass surfaces as a worker error and exits non-zero. |

```bash
wp mailpoet cron run-daemon
wp mailpoet cron run-daemon --timeout=30
```

### `wp mailpoet cron add`

Adds a new task to the schedule, optionally running it immediately in the CLI process.

Only standard (addable) worker types can be added — see [`types`](#wp-mailpoet-cron-types). The mailing
`sending` and `stats_notification` types are created by app flows (scheduling a newsletter, etc.) and
are rejected.

By default the task is due now at low priority. If a task of the type is already scheduled, the
command reports the existing task and does nothing unless `--force` is given.

With `--run`, the task is created already claimed (status `cli`, hidden from the web daemon) and
processed in-CLI immediately. `--run` bypasses the duplicate check (the claimed row is independent)
and cannot be combined with `--at`/`--in`.

| Argument / option       | Description                                                                                 |
| ----------------------- | ------------------------------------------------------------------------------------------- |
| `<type>`                | The (addable) task type to add.                                                             |
| `--at=<datetime>`       | Schedule for this date/time (e.g. `'2026-01-01 09:00'`, `'tomorrow 8am'`). Defaults to now. |
| `--in=<seconds>`        | Schedule this many seconds from now. Cannot be combined with `--at`.                        |
| `--priority=<priority>` | `high`, `medium`, or `low` (default). Lower runs sooner.                                    |
| `--force`               | Add the task even if one of the type is already scheduled.                                  |
| `--run`                 | Claim and run the task in this process now. Cannot be combined with `--at`/`--in`.          |

```bash
wp mailpoet cron add log_cleanup
wp mailpoet cron add bounce --in=3600 --priority=high
wp mailpoet cron add bounce --at='tomorrow 8am'
wp mailpoet cron add log_cleanup --force
wp mailpoet cron add subscribers_count_cache_recalculation --run
```

### `wp mailpoet cron cancel`

Cancels a task by setting its status to `cancelled`. Only `scheduled`, `paused`, and `cli` tasks can
be cancelled — running tasks are owned by the site daemon and completed ones are history. Cancelling a
`cli` task is the recovery path for a stuck CLI claim (see [Zombie CLI tasks](#zombie-cli-tasks)).

| Argument    | Description                                                  |
| ----------- | ------------------------------------------------------------ |
| `<task-id>` | The ID of the task to cancel (from `wp mailpoet cron list`). |

```bash
wp mailpoet cron cancel 42
```

## CLI execution: the `cli` status

`run`, `run-daemon`, and `add --run` all execute work **inside the WP-CLI process** rather than the
site's web-request daemon. The 20-second execution limit that bounds web-triggered runs is lifted by
default, so a long job runs to completion in one invocation (use `--timeout` to restore a cap).

When a task is run individually (`run --task-id`, `add --run`), it is **claimed** by giving it a
dedicated `cli` status. Every daemon-side query selects only known statuses (`null`/running and
`scheduled`), so a `cli` row is naturally invisible to the site daemon: it can neither pick the task
up nor reschedule it out from under the CLI run. This is what prevents two workers (CLI and web)
processing the same task at once.

> **Why a dedicated status rather than the in-progress flag?** An earlier design claimed a task by
> setting it to `running` (`NULL`) with an in-progress flag. That left two holes: the daemon's
> stuck-task rescue would steal a long-running CLI task after 120 minutes, and workers that support
> multiple instances skipped the in-progress guard entirely and would run the claimed task in
> parallel. A distinct status closes both with no changes to the daemon.

Outcomes of a claimed run:

- **Completed** → status `completed`, and a `meta.cli` breadcrumb (`pid`, `started_at`) is recorded so
  you can see the task was run from the CLI. The breadcrumb is written at completion (after the
  worker's own writes), so workers that rewrite their task meta mid-run can't clobber it.
- **Partial, or the worker throws** → the task is handed back to the site cron (status `scheduled`,
  due now) so the daemon continues or retries it. A `cli` row must never be left behind when the CLI
  isn't going to finish it.
- **Requirements not met** → the task is removed, exactly as the daemon does for such tasks.

### Zombie CLI tasks

If a CLI process is hard-killed (e.g. `kill -9`) mid-run, its task stays in `cli` status. This is
deliberate — there is no automatic cleanup. The stuck `cli` row is harmless (it is invisible to the
daemon, so it blocks nothing) and serves as evidence that a CLI run died. Recover it manually with
`wp mailpoet cron cancel <id>`, then re-add the work if needed.
