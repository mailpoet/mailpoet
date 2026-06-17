<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use Throwable;
use WP_CLI;
use WP_CLI\Formatter;

class CronCommand {
  private ScheduledTasksLister $scheduledTasksLister;

  private WorkerTypesCatalog $workerTypesCatalog;

  private TaskTrigger $taskTrigger;

  public function __construct(
    ScheduledTasksLister $scheduledTasksLister,
    WorkerTypesCatalog $workerTypesCatalog,
    TaskTrigger $taskTrigger
  ) {
    $this->scheduledTasksLister = $scheduledTasksLister;
    $this->workerTypesCatalog = $workerTypesCatalog;
    $this->taskTrigger = $taskTrigger;
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
}
