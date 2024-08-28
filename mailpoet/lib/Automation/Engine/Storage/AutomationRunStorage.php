<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Exceptions;

class AutomationRunStorage {
  /** @var string */
  private $table;

  /** @var string */
  private $subjectTable;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_runs';
    $this->subjectTable = $wpdb->prefix . 'mailpoet_automation_run_subjects';
  }

  public function createAutomationRun(AutomationRun $automationRun): int {
    global $wpdb;
    $automationTableData = $automationRun->toArray();
    $subjectTableData = $automationTableData['subjects'];
    unset($automationTableData['subjects']);
    $result = $wpdb->insert($this->table, $automationTableData);
    if ($result === false) {
      $this->throwDatabaseError();
    }
    $automationRunId = $wpdb->insert_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    if (!$subjectTableData) {
      //We allow for AutomationRuns with no subjects.
      return $automationRunId;
    }


    $values = [];
    foreach ($subjectTableData as $entry) {
      $values[] = $wpdb->prepare('(%d,%s,%s,%s)', $automationRunId, $entry['key'], $entry['args'], $entry['hash']);
    }

    $result = $wpdb->query(
      $wpdb->prepare('INSERT INTO %i (`automation_run_id`, `key`, `args`, `hash`) VALUES ', $this->subjectTable) . implode(',', $values)
    );
    if ($result === false) {
      $this->throwDatabaseError();
    }

    return $automationRunId;
  }

  public function getAutomationRun(int $id): ?AutomationRun {
    global $wpdb;

    $data = $wpdb->get_row(
      $wpdb->prepare('SELECT * FROM %i WHERE id = %d', $this->table, $id),
      ARRAY_A
    );

    if (!is_array($data) || !$data) {
      return null;
    }

    $subjects = $wpdb->get_results(
      $wpdb->prepare('SELECT * FROM %i WHERE automation_run_id = %d', $this->subjectTable, $id),
      ARRAY_A
    );
    $data['subjects'] = is_array($subjects) ? $subjects : [];
    return AutomationRun::fromArray((array)$data);
  }

  /**
   * @param Automation $automation
   * @return AutomationRun[]
   */
  public function getAutomationRunsForAutomation(Automation $automation): array {
    global $wpdb;

    $automationRuns = $wpdb->get_results(
      $wpdb->prepare(
        'SELECT * FROM %i WHERE automation_id = %d ORDER BY id',
        $this->table,
        $automation->getId()
      ),
      ARRAY_A
    );
    if (!is_array($automationRuns) || !$automationRuns) {
      return [];
    }

    $automationRunIds = array_column($automationRuns, 'id');
    $subjects = $wpdb->get_results(
      $wpdb->prepare(
        '
          SELECT *
          FROM %i
          WHERE automation_run_id IN (' . implode(',', array_fill(0, count($automationRunIds), '%s')) . ')
          ORDER BY automation_run_id, id
        ',
        array_merge(
          [$this->subjectTable],
          $automationRunIds,
        )
      ),
      ARRAY_A
    );

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
    global $wpdb;

    $result = $wpdb->get_col(
      $wpdb->prepare(
        '
          SELECT count(DISTINCT runs.id) AS count FROM %i AS runs
          JOIN %i AS subjects ON runs.id = subjects.automation_run_id
          WHERE runs.automation_id = %d
          AND subjects.hash = %s
        ',
        $this->table,
        $this->subjectTable,
        $automation->getId(),
        $subject->getHash()
      )
    );

    return $result ? (int)current($result) : 0;
  }

  public function getCountForAutomation(Automation $automation, string ...$status): int {
    global $wpdb;

    if (!count($status)) {
      $result = $wpdb->get_col(
        $wpdb->prepare(
          'SELECT COUNT(id) as count FROM %i WHERE automation_id = %d',
          $this->table,
          $automation->getId()
        )
      );
      return $result ? (int)current($result) : 0;
    }

    $result = $wpdb->get_col(
      // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The number of replacements is dynamic.
      $wpdb->prepare(
        '
          SELECT COUNT(id) as count
          FROM %i
          WHERE automation_id = %d
          AND status IN (' . implode(',', array_fill(0, count($status), '%s')) . ')
        ',
        $this->table,
        $automation->getId(),
        ...$status
      )
    );
    return $result ? (int)current($result) : 0;
  }

  public function updateStatus(int $id, string $status): void {
    global $wpdb;
    $result = $wpdb->query(
      $wpdb->prepare(
        '
          UPDATE %i
          SET status = %s, updated_at = current_timestamp()
          WHERE id = %d
        ',
        $this->table,
        $status,
        $id
      )
    );
    if ($result === false) {
      $this->throwDatabaseError();
    }
  }

  public function updateNextStep(int $id, ?string $nextStepId): void {
    global $wpdb;
    $result = $wpdb->query(
      $wpdb->prepare(
        '
          UPDATE %i
          SET next_step_id = %s, updated_at = current_timestamp()
          WHERE id = %d
        ',
        $this->table,
        $nextStepId,
        $id
      )
    );
    if ($result === false) {
      $this->throwDatabaseError();
    }
  }

  public function getAutomationStepStatisticForTimeFrame(int $automationId, string $status, \DateTimeImmutable $after, \DateTimeImmutable $before, int $versionId = null): array {
    global $wpdb;
    $andWhere = $versionId ? 'AND version_id = %d' : '';
    $result = $wpdb->get_results(
      // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The number of replacements is dynamic.
      $wpdb->prepare(
        '
          SELECT COUNT(id) AS count, next_step_id
          FROM %i AS log
          WHERE automation_id = %d AND status = %s AND created_at BETWEEN %s AND %s
          ' . $andWhere . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The condition uses placeholders. */ '
          GROUP BY next_step_id
        ',
        array_merge(
          [
            $this->table,
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
    return is_array($result) ? $result : [];
  }

  public function truncate(): void {
    global $wpdb;
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $this->table));
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $this->subjectTable));
  }

  private function throwDatabaseError(): void {
    global $wpdb;
    throw Exceptions::databaseError($wpdb->last_error); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
