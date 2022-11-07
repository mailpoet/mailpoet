<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Integration\Trigger;
use wpdb;

class WorkflowStorage {
  /** @var string */
  private $workflowsTable;

  /** @var string */
  private $versionsTable;

  /** @var string */
  private $triggersTable;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->workflowsTable = $wpdb->prefix . 'mailpoet_workflows';
    $this->versionsTable = $wpdb->prefix . 'mailpoet_workflow_versions';
    $this->triggersTable = $wpdb->prefix . 'mailpoet_workflow_triggers';
    $this->wpdb = $wpdb;
  }

  public function createWorkflow(Workflow $workflow): int {
    $workflowHeaderData = $this->getWorkflowHeaderData($workflow);
    unset($workflowHeaderData['id']);
    $result = $this->wpdb->insert($this->workflowsTable, $workflowHeaderData);
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $id = $this->wpdb->insert_id;
    $this->insertWorkflowVersion($id, $workflow);
    $this->insertWorkflowTriggers($id, $workflow);
    return $id;
  }

  public function updateWorkflow(Workflow $workflow): void {
    $oldRecord = $this->getWorkflow($workflow->getId());
    if ($oldRecord && $oldRecord->equals($workflow)) {
      return;
    }
    $result = $this->wpdb->update($this->workflowsTable, $this->getWorkflowHeaderData($workflow), ['id' => $workflow->getId()]);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $this->insertWorkflowVersion($workflow->getId(), $workflow);
    $this->insertWorkflowTriggers($workflow->getId(), $workflow);
  }

  public function getWorkflow(int $workflowId, int $versionId = null): ?Workflow {
    $workflowsTable = esc_sql($this->workflowsTable);
    $versionsTable = esc_sql($this->versionsTable);

    $query = !$versionId ? (string)$this->wpdb->prepare("
      SELECT workflow.*, version.id AS version_id, version.steps
      FROM $workflowsTable as workflow, $versionsTable as version
      WHERE version.workflow_id = workflow.id AND workflow.id = %d
      ORDER BY version.id DESC
      LIMIT 0,1;",
      $workflowId
    ) : (string)$this->wpdb->prepare("
      SELECT workflow.*, version.id AS version_id, version.steps
      FROM $workflowsTable as workflow, $versionsTable as version
      WHERE version.workflow_id = workflow.id AND version.id = %d",
      $versionId
    );
    $data = $this->wpdb->get_row($query, ARRAY_A);
    return $data ? Workflow::fromArray((array)$data) : null;
  }

  /** @return Workflow[] */
  public function getWorkflows(array $status = null): array {
    $workflowsTable = esc_sql($this->workflowsTable);
    $versionsTable = esc_sql($this->versionsTable);
    $query = $status ?
      (string)$this->wpdb->prepare("
        SELECT workflow.*, version.id AS version_id, version.steps
        FROM $workflowsTable AS workflow INNER JOIN $versionsTable as version ON (version.workflow_id=workflow.id)
        WHERE version.id = (SELECT Max(id) FROM $versionsTable WHERE workflow_id= version.workflow_id) AND workflow.status IN (%s)
        ORDER BY workflow.id DESC",
        implode(",", $status)
      ) :
      "SELECT workflow.*, version.id AS version_id, version.steps
      FROM $workflowsTable AS workflow INNER JOIN $versionsTable as version ON (version.workflow_id=workflow.id)
      WHERE version.id = (SELECT Max(id) FROM $versionsTable WHERE workflow_id= version.workflow_id)
      ORDER BY workflow.id DESC;";

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }

  public function getWorkflowCount(): int {
    $workflowsTable = esc_sql($this->workflowsTable);
    return (int)$this->wpdb->get_var("SELECT COUNT(*) FROM $workflowsTable");
  }

  /** @return string[] */
  public function getActiveTriggerKeys(): array {
    $workflowsTable = esc_sql($this->workflowsTable);
    $triggersTable = esc_sql($this->triggersTable);

    $query = (string)$this->wpdb->prepare(
      "
        SELECT DISTINCT triggers.trigger_key
        FROM {$workflowsTable} AS workflow
        JOIN $triggersTable as triggers
        WHERE workflow.status = %s AND workflow.id = triggers.workflow_id
        ORDER BY trigger_key DESC
      ",
      Workflow::STATUS_ACTIVE
    );
    return $this->wpdb->get_col($query);
  }

  /** @return Workflow[] */
  public function getActiveWorkflowsByTrigger(Trigger $trigger): array {
    $workflowsTable = esc_sql($this->workflowsTable);
    $versionsTable = esc_sql($this->versionsTable);
    $triggersTable = esc_sql($this->triggersTable);

    $query = (string)$this->wpdb->prepare(
      "
        SELECT workflow.*, version.id AS version_id, version.steps
        FROM $workflowsTable AS workflow
        INNER JOIN $triggersTable as t ON (t.workflow_id = workflow.id)
        INNER JOIN $versionsTable as version ON (version.workflow_id = workflow.id)
        WHERE workflow.status = %s
        AND t.trigger_key = %s
        AND version.id = (
          SELECT MAX(id) FROM $versionsTable WHERE workflow_id = version.workflow_id
        )
      ",
      Workflow::STATUS_ACTIVE,
      $trigger->getKey()
    );

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }

  public function deleteWorkflow(Workflow $workflow): void {
    $workflowsTable = esc_sql($this->workflowsTable);
    $versionsTable = esc_sql($this->versionsTable);
    $workflowRunsTable = esc_sql($this->wpdb->prefix . 'mailpoet_workflow_runs');
    $workflowRunLogsTable = esc_sql($this->wpdb->prefix . 'mailpoet_workflow_run_logs');
    $workflowId = $workflow->getId();
    $runLogsQuery = $this->wpdb->prepare(
      "
        DELETE FROM $workflowRunLogsTable
        WHERE workflow_run_id IN (
          SELECT id FROM $workflowRunsTable
          WHERE workflow_id = %d
        )
      ",
      $workflowId
    );

    if (!is_string($runLogsQuery)) {
      throw Exceptions\InvalidStateException::create();
    }
    $logsDeleted = $this->wpdb->query($runLogsQuery);
    if (!is_int($logsDeleted)) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $runsDeleted = $this->wpdb->delete($this->wpdb->prefix . 'mailpoet_workflow_runs', ['workflow_id' => $workflowId]);
    if (!is_int($runsDeleted)) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $versionsDeleted = $this->wpdb->delete($versionsTable, ['workflow_id' => $workflowId]);
    if (!is_int($versionsDeleted)) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $triggersDeleted = $this->wpdb->delete($this->triggersTable, ['workflow_id' => $workflowId]);
    if (!is_int($triggersDeleted)) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $workflowDeleted = $this->wpdb->delete($workflowsTable, ['id' => $workflowId]);
    if (!is_int($workflowDeleted)) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function truncate(): bool {
    $workflowsTable = esc_sql($this->workflowsTable);
    $versionsTable = esc_sql($this->versionsTable);
    $triggersTable = esc_sql($this->versionsTable);
    return $this->wpdb->query("truncate $workflowsTable;") === true
      && $this->wpdb->query("truncate $versionsTable;") === true
      && $this->wpdb->query("truncate $triggersTable;") === true;
  }

  public function getNameColumnLength(): int {
    $nameColumnLengthInfo = $this->wpdb->get_col_length($this->workflowsTable, 'name');
    return is_array($nameColumnLengthInfo)
      ? $nameColumnLengthInfo['length'] ?? 255
      : 255;
  }

  private function getWorkflowHeaderData(Workflow $workflow): array {
    $workflowHeader = $workflow->toArray();
    unset($workflowHeader['steps']);
    return $workflowHeader;
  }

  private function insertWorkflowVersion(int $workflowId, Workflow $workflow): void {
    $dateString = (new DateTimeImmutable())->format(DateTimeImmutable::W3C);
    $data = [
      'workflow_id' => $workflowId,
      'steps' => $workflow->toArray()['steps'],
      'created_at' => $dateString,
      'updated_at' => $dateString,
    ];
    $result = $this->wpdb->insert($this->versionsTable, $data);
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  private function insertWorkflowTriggers(int $workflowId, Workflow $workflow): void {
    $triggerKeys = [];
    foreach ($workflow->getSteps() as $step) {
      if ($step->getType() === Step::TYPE_TRIGGER) {
        $triggerKeys[] = $step->getKey();
      }
    }

    if (!$triggerKeys) {
      return;
    }
    $triggersTable = esc_sql($this->triggersTable);

    // insert/update
    $placeholders = implode(',', array_fill(0, count($triggerKeys), '(%d, %s)'));
    $query = (string)$this->wpdb->prepare(
      "INSERT IGNORE INTO {$triggersTable} (workflow_id, trigger_key) VALUES {$placeholders}",
      array_merge(
        ...array_map(function (string $key) use ($workflowId) {
          return [$workflowId, $key];
        }, $triggerKeys)
      )
    );

    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    // delete
    $placeholders = implode(',', array_fill(0, count($triggerKeys), '%s'));
    $query = (string)$this->wpdb->prepare(
      "DELETE FROM {$triggersTable} WHERE workflow_id = %d AND trigger_key NOT IN ({$placeholders})",
      array_merge([$workflowId], $triggerKeys)
    );

    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }
}
