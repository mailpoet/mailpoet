<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use Throwable;
use WP_CLI;
use WP_CLI\Formatter;

class CronCommand {
  private ScheduledTasksLister $scheduledTasksLister;

  private WorkerTypesCatalog $workerTypesCatalog;

  private TaskTrigger $taskTrigger;

  private TaskRunner $taskRunner;

  private TaskCanceller $taskCanceller;

  private DaemonRunner $daemonRunner;

  private TaskAdder $taskAdder;

  public function __construct(
    ScheduledTasksLister $scheduledTasksLister,
    WorkerTypesCatalog $workerTypesCatalog,
    TaskTrigger $taskTrigger,
    TaskRunner $taskRunner,
    TaskCanceller $taskCanceller,
    DaemonRunner $daemonRunner,
    TaskAdder $taskAdder
  ) {
    $this->scheduledTasksLister = $scheduledTasksLister;
    $this->workerTypesCatalog = $workerTypesCatalog;
    $this->taskTrigger = $taskTrigger;
    $this->taskRunner = $taskRunner;
    $this->taskCanceller = $taskCanceller;
    $this->daemonRunner = $daemonRunner;
    $this->taskAdder = $taskAdder;
  }

  /**
   * Lists MailPoet scheduled tasks.
   *
   * ## OPTIONS
   *
   * [--status=<status>]
   * : Filter tasks by status. Defaults to actionable tasks (scheduled, running, and cli).
   * ---
   * options:
   *   - scheduled
   *   - running
   *   - cli
   *   - completed
   *   - cancelled
   *   - paused
   *   - invalid
   *   - all
   * ---
   *
   * [--type=<type>]
   * : Filter tasks by type.
   *
   * [--limit=<n>]
   * : Maximum number of tasks to return. Default 50.
   *
   * [--field=<field>]
   * : Print the value of a single field for each task.
   *
   * [--fields=<fields>]
   * : Limit the output to specific fields. Comma-separated.
   *
   * [--format=<format>]
   * : Render output in a particular format.
   * ---
   * default: table
   * options:
   *   - table
   *   - csv
   *   - json
   *   - ids
   *   - count
   * ---
   *
   * ## EXAMPLES
   *
   *     wp mailpoet cron list
   *     wp mailpoet cron list --status=running
   *     wp mailpoet cron list --status=all --format=json
   *     wp mailpoet cron list --type=sending --limit=10
   *
   * @subcommand list
   *
   * @param array $args
   * @param array $assocArgs
   */
  public function list(array $args, array $assocArgs): void {
    $status = isset($assocArgs['status']) ? (string)$assocArgs['status'] : null;
    $type = isset($assocArgs['type']) ? (string)$assocArgs['type'] : null;
    $limit = array_key_exists('limit', $assocArgs) ? (int)$assocArgs['limit'] : ScheduledTasksLister::DEFAULT_LIMIT;
    $format = $assocArgs['format'] ?? 'table';

    try {
      $rows = $this->scheduledTasksLister->getRows($status, $type, $limit);
    } catch (Throwable $e) {
      WP_CLI::error($e->getMessage());
      return;
    }

    // The Formatter constructor consumes the format/fields keys from $assocArgs (by reference),
    // so read the format before constructing it.
    $formatter = new Formatter($assocArgs, ScheduledTasksLister::FIELDS);
    if ($format === 'ids') {
      $formatter->display_items(array_column($rows, 'id'));
      return;
    }
    $formatter->display_items($rows);
  }

  /**
   * Lists all known MailPoet cron worker task types and their attributes.
   *
   * ## OPTIONS
   *
   * [--fields=<fields>]
   * : Limit the output to specific fields. Comma-separated.
   *
   * [--field=<field>]
   * : Print the value of a single field for each type.
   *
   * [--format=<format>]
   * : Render output in a particular format.
   * ---
   * default: table
   * options:
   *   - table
   *   - csv
   *   - json
   *   - yaml
   *   - count
   * ---
   *
   * ## EXAMPLES
   *
   *     wp mailpoet cron types
   *     wp mailpoet cron types --format=json
   *     wp mailpoet cron types --fields=type,addable
   *
   * @subcommand types
   *
   * @param array $args
   * @param array $assocArgs
   */
  public function types(array $args, array $assocArgs): void {
    $rows = $this->workerTypesCatalog->getRows();

    $formatter = new Formatter($assocArgs, WorkerTypesCatalog::FIELDS);
    $formatter->display_items($rows);
  }

  /**
   * Marks a MailPoet cron task as due now so the site's own cron processor picks it up.
   *
   * This does not kick the cron pipeline; the MailPoet cron runner runs the task on its next tick.
   * By type it targets the next scheduled task of that type; with --task-id it targets an exact row
   * and also re-schedules a paused one.
   *
   * ## OPTIONS
   *
   * <type>
   * : The task type to trigger. See `wp mailpoet cron types` for valid values.
   *
   * [--task-id=<id>]
   * : Trigger an exact task by ID instead of the next scheduled task of the type.
   *
   * ## EXAMPLES
   *
   *     wp mailpoet cron trigger sending
   *     wp mailpoet cron trigger bounce --task-id=42
   *
   * @subcommand trigger
   *
   * @param array $args
   * @param array $assocArgs
   */
  public function trigger(array $args, array $assocArgs): void {
    $type = (string)$args[0];
    $taskId = array_key_exists('task-id', $assocArgs) ? (int)$assocArgs['task-id'] : null;

    try {
      $triggered = $this->taskTrigger->trigger($type, $taskId);
    } catch (Throwable $e) {
      WP_CLI::error($e->getMessage());
      return;
    }

    WP_CLI::success(sprintf(
      "Task %d (%s) is now due. The MailPoet cron runner will pick it up on its next tick.",
      $triggered['id'],
      $triggered['type']
    ));
  }

  /**
   * Runs a MailPoet cron worker inside this WP-CLI process.
   *
   * Without --task-id, it snapshots the currently-due tasks of the given type and runs each once,
   * claiming it as 'cli' (invisible to the site daemon) so the daemon never double-processes it.
   * Self-rescheduling batched workers (e.g. subscribers_engagement_score) process one batch per due
   * task and leave a continuation for the site cron, so run again (or `trigger`) to process the rest.
   * The 20-second execution limit is lifted by default; pass --timeout to cap it.
   *
   * ## OPTIONS
   *
   * <type>
   * : The task type to run. See `wp mailpoet cron types` for valid values.
   *
   * [--task-id=<id>]
   * : Run this exact task in-process: it is claimed (status 'cli', hidden from the web daemon) and run
   * here, so the daemon never double-processes it. Only scheduled or paused tasks can be run by ID.
   *
   * [--timeout=<seconds>]
   * : Cap the run at this many seconds (restores an execution limit). Omit to run to completion.
   *
   * ## EXAMPLES
   *
   *     wp mailpoet cron run log_cleanup
   *     wp mailpoet cron run sending
   *     wp mailpoet cron run bounce --task-id=42
   *     wp mailpoet cron run sending --timeout=30
   *
   * @subcommand run
   *
   * @param array $args
   * @param array $assocArgs
   */
  public function run(array $args, array $assocArgs): void {
    $type = (string)$args[0];
    $taskId = array_key_exists('task-id', $assocArgs) ? (int)$assocArgs['task-id'] : null;
    $timeout = array_key_exists('timeout', $assocArgs) ? (int)$assocArgs['timeout'] : null;

    try {
      $result = $this->taskRunner->run($type, $taskId, $timeout);
    } catch (Throwable $e) {
      WP_CLI::error($e->getMessage());
      return;
    }

    // limit_reached and an un-drained backlog are both non-fatal partial outcomes (still exit 0):
    // warn so the operator sees that not everything ran. A fully drained backlog is a success.
    if ($result['limit_reached'] || !$result['backlog_drained']) {
      WP_CLI::warning($result['message']);
      return;
    }

    WP_CLI::success($result['message']);
  }

  /**
   * Runs one full MailPoet daemon pass over all cron workers inside this WP-CLI process.
   *
   * Each worker runs once, processing the tasks that are due. This is a single daemon pass, not a
   * backlog drain: a worker with many due tasks may need several passes, or use
   * `wp mailpoet cron run <type>` to drain one type. The 20-second execution limit is lifted by
   * default; pass --timeout to cap it. Per-worker errors collected during the pass are printed and
   * make the command exit non-zero.
   *
   * ## OPTIONS
   *
   * [--timeout=<seconds>]
   * : Cap the pass at this many seconds (restores an execution limit). Omit to run to completion.
   * Unlike `run`, hitting --timeout mid-pass surfaces as a worker error and exits non-zero.
   *
   * ## EXAMPLES
   *
   *     wp mailpoet cron run-daemon
   *     wp mailpoet cron run-daemon --timeout=30
   *
   * @subcommand run-daemon
   *
   * @param array $args
   * @param array $assocArgs
   */
  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WP-CLI maps the run_daemon method to the run-daemon subcommand; the underscore is required.
  public function run_daemon(array $args, array $assocArgs): void {
    $timeout = array_key_exists('timeout', $assocArgs) ? (int)$assocArgs['timeout'] : null;

    try {
      $result = $this->daemonRunner->run($timeout);
    } catch (Throwable $e) {
      WP_CLI::error($e->getMessage());
      return;
    }

    $errors = $result['errors'];
    if (empty($errors)) {
      WP_CLI::success('Daemon pass finished. Note: large backlogs may need multiple passes or `wp mailpoet cron run <type>`.');
      return;
    }

    foreach ($errors as $error) {
      WP_CLI::warning(sprintf('%s: %s', $error['worker'], $error['message']));
    }
    WP_CLI::error(sprintf('Daemon pass finished with %d worker error(s).', count($errors)));
  }

  /**
   * Adds a new MailPoet cron task to the schedule, optionally running it immediately in this process.
   *
   * Only standard worker types are addable; see `wp mailpoet cron types` (the mailing 'sending' and
   * 'stats_notification' rows are created by app flows and are rejected). By default the task is due
   * now at low priority. If a task of the type is already scheduled the command reports it and does
   * nothing unless --force is given. With --run the task is created already claimed (status 'cli',
   * hidden from the web daemon) and processed in-CLI, bypassing the web daemon and the duplicate check.
   *
   * ## OPTIONS
   *
   * <type>
   * : The task type to add. See `wp mailpoet cron types` for addable values.
   *
   * [--at=<datetime>]
   * : Schedule for this date/time (e.g. '2026-01-01 09:00', 'tomorrow 8am'). Defaults to now.
   *
   * [--in=<seconds>]
   * : Schedule this many seconds from now. Cannot be combined with --at.
   *
   * [--priority=<priority>]
   * : Task priority. Lower runs sooner.
   * ---
   * default: low
   * options:
   *   - high
   *   - medium
   *   - low
   * ---
   *
   * [--force]
   * : Add the task even if one of the type is already scheduled.
   *
   * [--run]
   * : Claim the task (status 'cli', hidden from the web daemon) and run it in this process now. Cannot
   * be combined with --at/--in.
   *
   * ## EXAMPLES
   *
   *     wp mailpoet cron add log_cleanup
   *     wp mailpoet cron add bounce --in=3600 --priority=high
   *     wp mailpoet cron add bounce --at='tomorrow 8am'
   *     wp mailpoet cron add log_cleanup --force
   *     wp mailpoet cron add subscribers_count_cache_recalculation --run
   *
   * @subcommand add
   *
   * @param array $args
   * @param array $assocArgs
   */
  public function add(array $args, array $assocArgs): void {
    $type = (string)$args[0];
    $at = array_key_exists('at', $assocArgs) ? (string)$assocArgs['at'] : null;
    $in = array_key_exists('in', $assocArgs) ? (int)$assocArgs['in'] : null;
    $priority = array_key_exists('priority', $assocArgs) ? (string)$assocArgs['priority'] : 'low';
    $force = (bool)($assocArgs['force'] ?? false);
    $run = (bool)($assocArgs['run'] ?? false);

    try {
      $result = $this->taskAdder->add($type, $at, $in, $priority, $force, $run);
    } catch (Throwable $e) {
      WP_CLI::error($e->getMessage());
      return;
    }

    if ($result['action'] === 'duplicate') {
      WP_CLI::warning($result['message']);
      return;
    }

    WP_CLI::success($result['message']);

    if ($result['run'] !== null) {
      if ($result['run']['completed']) {
        WP_CLI::success($result['run']['message']);
      } else {
        WP_CLI::warning($result['run']['message']);
      }
    }
  }

  /**
   * Cancels a MailPoet cron task by setting its status to cancelled.
   *
   * Scheduled, paused, and cli tasks can be cancelled; cancelling a cli task recovers a stuck CLI
   * claim. Running tasks are owned by their executor and completed ones are history, so both are
   * rejected.
   *
   * ## OPTIONS
   *
   * <task-id>
   * : The ID of the task to cancel. See `wp mailpoet cron list` for task IDs.
   *
   * ## EXAMPLES
   *
   *     wp mailpoet cron cancel 42
   *
   * @subcommand cancel
   *
   * @param array $args
   * @param array $assocArgs
   */
  public function cancel(array $args, array $assocArgs): void {
    $taskId = (int)$args[0];

    try {
      $cancelled = $this->taskCanceller->cancel($taskId);
    } catch (Throwable $e) {
      WP_CLI::error($e->getMessage());
      return;
    }

    WP_CLI::success(sprintf(
      "Task %d (%s) cancelled.",
      $cancelled['id'],
      $cancelled['type']
    ));
  }
}
