<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\InvalidStateException;

class AutomationRunLogStorage {
  /** @var string */
  private $table;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_run_logs';
  }

  public function createAutomationRunLog(AutomationRunLog $automationRunLog): int {
    global $wpdb;
    $result = $wpdb->insert($this->table, $automationRunLog->toArray());
    if ($result === false) {
      $this->throwDatabaseError();
    }
    return $wpdb->insert_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function updateAutomationRunLog(AutomationRunLog $automationRunLog): void {
    global $wpdb;
    $result = $wpdb->update($this->table, $automationRunLog->toArray(), ['id' => $automationRunLog->getId()]);
    if ($result === false) {
      $this->throwDatabaseError();
    }
  }

  public function getAutomationRunStatisticsForAutomationInTimeFrame(int $automationId, string $status, \DateTimeImmutable $after, \DateTimeImmutable $before, int $versionId = null): array {
    global $wpdb;
    $andWhere = $versionId ? 'AND run.version_id = %d' : '';
    $results = $wpdb->get_results(
      // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The number of replacements is dynamic.
      $wpdb->prepare(
        '
          SELECT COUNT(log.id) AS count, log.step_id
          FROM %i AS log
          JOIN %i AS run ON log.automation_run_id = run.id
          WHERE run.automation_id = %d AND log.status = %s AND run.created_at BETWEEN %s AND %s
          ' . $andWhere . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The condition uses placeholders. */ '
          GROUP BY log.step_id
        ',
        array_merge(
          [
            $this->table,
            $wpdb->prefix . 'mailpoet_automation_runs',
            $automationId,
            $status,
            $after->format('Y-m-d H:i:s'),
            $before->format('Y-m-d H:i:s'),
          ],
          $versionId ? [$versionId] : []
        )
      ),
      ARRAY_A
    );
    return is_array($results) ? $results : [];
  }

  public function getAutomationRunLog(int $id): ?AutomationRunLog {
    global $wpdb;

    $result = $wpdb->get_row(
      $wpdb->prepare('SELECT * FROM %i WHERE id = %d', $this->table, $id),
      ARRAY_A
    );

    if ($result) {
      $data = (array)$result;
      return AutomationRunLog::fromArray($data);
    }
    return null;
  }

  public function getAutomationRunLogByRunAndStepId(int $runId, string $stepId): ?AutomationRunLog {
    global $wpdb;
    $result = $wpdb->get_row(
      $wpdb->prepare('SELECT * FROM %i WHERE automation_run_id = %d AND step_id = %s', $this->table, $runId, $stepId),
      ARRAY_A
    );
    return $result ? AutomationRunLog::fromArray((array)$result) : null;
  }

  /** @return AutomationRunLog[] */
  public function getLogsForAutomationRun(int $automationRunId): array {
    global $wpdb;

    $results = $wpdb->get_results(
      $wpdb->prepare(
        '
          SELECT *
          FROM %i
          WHERE automation_run_id = %d
          ORDER BY id ASC
        ',
        $this->table,
        $automationRunId
      ),
      ARRAY_A
    );

    if (!is_array($results)) {
      throw InvalidStateException::create();
    }

    if ($results) {
      return array_map(function($data) {
        /** @var array $data - for PHPStan because it conflicts with expected callable(mixed): mixed)|null */
        return AutomationRunLog::fromArray($data);
      }, $results);
    }

    return [];
  }

  public function truncate(): void {
    global $wpdb;
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $this->table));
  }

  private function throwDatabaseError(): void {
    global $wpdb;
    throw Exceptions::databaseError($wpdb->last_error); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
