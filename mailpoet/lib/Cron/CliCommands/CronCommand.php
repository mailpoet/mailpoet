<?php declare(strict_types = 1);

namespace MailPoet\Cron\CliCommands;

use Throwable;
use WP_CLI;
use WP_CLI\Formatter;

class CronCommand {
  private ScheduledTasksLister $scheduledTasksLister;

  public function __construct(
    ScheduledTasksLister $scheduledTasksLister
  ) {
    $this->scheduledTasksLister = $scheduledTasksLister;
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
}
