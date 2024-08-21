<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Exceptions;
use wpdb;

class AutomationRunStorage {
  /** @var string */
  private $table;

  /** @var string */
  private $subjectTable;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_runs';
    $this->subjectTable = $wpdb->prefix . 'mailpoet_automation_run_subjects';
    $this->wpdb = $wpdb;
  }

  public function createAutomationRun(AutomationRun $automationRun): int {
    $automationTableData = $automationRun->toArray();
    $subjectTableData = $automationTableData['subjects'];
    unset($automationTableData['subjects']);
    $result = $this->wpdb->insert($this->table, $automationTableData);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $automationRunId = $this->wpdb->insert_id;

    if (!$subjectTableData) {
      //We allow for AutomationRuns with no subjects.
      return $automationRunId;
    }

    $sql = 'insert into ' . esc_sql($this->subjectTable) . ' (`automation_run_id`, `key`, `args`, `hash`) values %s';
    $values = [];
    foreach ($subjectTableData as $entry) {
      $values[] = (string)$this->wpdb->prepare("(%d,%s,%s,%s)", $automationRunId, $entry['key'], $entry['args'], $entry['hash']);
    }
    $sql = sprintf($sql, implode(',', $values));
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name is sanitized and dynamic values are prepared with placeholders
    $result = $this->wpdb->query($sql);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    return $automationRunId;
  }

  public function getAutomationRun(int $id): ?AutomationRun {
    $wpdb = $this->wpdb;

    $data = $wpdb->get_row((string)$wpdb->prepare("
      SELECT * FROM %i  WHERE id = %d
    ", $this->table, $id), ARRAY_A);

    if (!is_array($data) || !$data) {
      return null;
    }

    $subjects = $wpdb->get_results((string)$wpdb->prepare("
      SELECT * FROM %i WHERE automation_run_id = %d
    ", $this->subjectTable, $id), ARRAY_A);
    $data['subjects'] = is_array($subjects) ? $subjects : [];
    return AutomationRun::fromArray((array)$data);
  }

  /**
   * @param Automation $automation
   * @return AutomationRun[]
   */
  public function getAutomationRunsForAutomation(Automation $automation): array {
    $wpdb = $this->wpdb;

    $automationRuns = $wpdb->get_results((string)$wpdb->prepare("
      SELECT * FROM %i WHERE automation_id = %d order by id
    ", $this->table, $automation->getId()), ARRAY_A);
    if (!is_array($automationRuns) || !$automationRuns) {
      return [];
    }

    $automationRunIds = array_column($automationRuns, 'id');

    $whereCondition = sprintf(
      "WHERE automation_run_id in (%s)",
      implode(
        ',',
        array_map(
          function() {
            return '%d';
          },
          $automationRunIds
        )
      )
    );

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The variable $whereCondition only contains placeholders
    $subjects = $wpdb->get_results((string)$wpdb->prepare("
      SELECT *
      FROM %i
      $whereCondition
      ORDER BY automation_run_id, id
    ", ...array_merge([$this->subjectTable], $automationRunIds)), ARRAY_A); // The array_merge function is used to avoid PHPCS error about incorrect number of parameters in prepare function
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    return array_map(
      function($runData) use ($subjects): AutomationRun {
        /** @var array $runData - PHPStan expects as array_map first parameter (callable(mixed): mixed)|null */
        $runData['subjects'] = array_values(array_filter(
          is_array($subjects) ? $subjects : [],
          function($subjectData) use ($runData): bool {
            /** @var array $subjectData - PHPStan expects as array_map first parameter (callable(mixed): mixed)|null */
            return (int)$subjectData['automation_run_id'] === (int)$runData['id'];
          }
        ));
        return AutomationRun::fromArray($runData);
      },
      $automationRuns
    );
  }

  /**
   * @param Automation $automation
   * @return int
   */
  public function getCountByAutomationAndSubject(Automation $automation, Subject $subject): int {
    $wpdb = $this->wpdb;

    $result = $wpdb->get_col((string)$wpdb->prepare("
      SELECT count(DISTINCT runs.id) AS count FROM %i AS runs
      JOIN %i AS subjects ON runs.id = subjects.automation_run_id
      WHERE runs.automation_id = %d
      AND subjects.hash = %s
    ", $this->table, $this->subjectTable, $automation->getId(), $subject->getHash()));

    return $result ? (int)current($result) : 0;
  }

  public function getCountForAutomation(Automation $automation, string ...$status): int {
    $wpdb = $this->wpdb;

    if (!count($status)) {
      $result = $wpdb->get_col((string)$wpdb->prepare("
        SELECT COUNT(id) as count
        FROM %i
        WHERE automation_id = %d
      ", $this->table, $automation->getId()));
      return $result ? (int)current($result) : 0;
    }

    $statusSql = (string)$this->wpdb->prepare(implode(',', array_fill(0, count($status), '%s')), ...$status);
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The variable is prepared with placeholders
    $result = $wpdb->get_col((string)$wpdb->prepare("
      SELECT COUNT(id) as count
      FROM %i
      WHERE automation_id = %d
      AND status IN ($statusSql)
    ", $this->table, $automation->getId()));
    // php:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return $result ? (int)current($result) : 0;
  }

  public function updateStatus(int $id, string $status): void {
    $wpdb = $this->wpdb;
    $result = $wpdb->query((string)$wpdb->prepare("
      UPDATE %i
      SET status = %s, updated_at = current_timestamp()
      WHERE id = %d
    ", $this->table, $status, $id));
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function updateNextStep(int $id, ?string $nextStepId): void {
    $wpdb = $this->wpdb;
    $result = $wpdb->query((string)$wpdb->prepare("
      UPDATE %i
      SET next_step_id = %s, updated_at = current_timestamp()
      WHERE id = %d
    ", $this->table, $nextStepId, $id));
    if ($result === false) {
      throw Exceptions::databaseError($wpdb->last_error); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
  }

  public function getAutomationStepStatisticForTimeFrame(int $automationId, string $status, \DateTimeImmutable $after, \DateTimeImmutable $before, int $versionId = null): array {
    $table = esc_sql($this->table);

    $where = "automation_id = %d
    AND `status` = %s
    AND created_at BETWEEN %s AND %s";
    if ($versionId) {
      $where .= " AND version_id = %d";
    }
    /** @var literal-string $sql */
    $sql = "
      SELECT
        COUNT(id) AS `count`,
        next_step_id
      FROM $table as log
      WHERE $where
      GROUP BY next_step_id
    ";

    $sql = $versionId ?
      $this->wpdb->prepare($sql, $automationId, $status, $after->format('Y-m-d H:i:s'), $before->format('Y-m-d H:i:s'), $versionId) : // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name is sanitized and values are prepared with placeholders
      $this->wpdb->prepare($sql, $automationId, $status, $after->format('Y-m-d H:i:s'), $before->format('Y-m-d H:i:s')); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name is sanitized and values are prepared with placeholders
    $sql = is_string($sql) ? $sql : '';

    $result = $this->wpdb->get_results($sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name is sanitized and values are prepared with placeholders
    return is_array($result) ? $result : [];
  }

  public function truncate(): void {
    $wpdb = $this->wpdb;

    $wpdb->query((string)$wpdb->prepare("TRUNCATE %i", $this->table));
    $wpdb->query((string)$wpdb->prepare("TRUNCATE %i", $this->subjectTable));
  }
}
