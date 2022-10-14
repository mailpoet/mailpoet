<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Utils\Json;
use wpdb;

class WorkflowStorage {
  /** @var string */
  private $workflowTable;
  /** @var string */
  private $versionsTable;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->workflowTable = $wpdb->prefix . 'mailpoet_workflows';
    $this->versionsTable = $wpdb->prefix . 'mailpoet_workflow_versions';
    $this->wpdb = $wpdb;
  }

  public function createWorkflow(Workflow $workflow): int {
    $workflowHeaderData = $this->getWorkflowHeaderData($workflow);
    unset($workflowHeaderData['id']);
    $result = $this->wpdb->insert($this->workflowTable, $workflowHeaderData);
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $id = $this->wpdb->insert_id;
    $this->insertWorkflowVersion($id, $workflow);
    return $id;
  }

  public function updateWorkflow(Workflow $workflow): void {
    $oldRecord = $this->getWorkflow($workflow->getId());
    if ($oldRecord && $oldRecord->equals($workflow)) {
      return;
    }
    $result = $this->wpdb->update($this->workflowTable, $this->getWorkflowHeaderData($workflow), ['id' => $workflow->getId()]);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $this->insertWorkflowVersion($workflow->getId(), $workflow);
  }

  public function getWorkflow(int $workflowId, int $versionId = null): ?Workflow {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);

    $query = !$versionId ? (string)$this->wpdb->prepare("
      SELECT workflow.*, version.id AS version_id, version.steps, version.trigger_keys
      FROM $workflowTable as workflow, $versionTable as version
      WHERE version.workflow_id = workflow.id AND workflow.id = %d
      ORDER BY version.id DESC
      LIMIT 0,1;",
      $workflowId
    ) : (string)$this->wpdb->prepare("
      SELECT workflow.*, version.id AS version_id, version.steps, version.trigger_keys
      FROM $workflowTable as workflow, $versionTable as version
      WHERE version.workflow_id = workflow.id AND version.id = %d",
      $versionId
    );
    $data = $this->wpdb->get_row($query, ARRAY_A);
    return $data ? Workflow::fromArray((array)$data) : null;
  }

  /** @return Workflow[] */
  public function getWorkflows(array $status = null): array {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);
    $query = $status ?
      (string)$this->wpdb->prepare("
        SELECT workflow.*, version.id AS version_id, version.steps, version.trigger_keys
        FROM $workflowTable AS workflow INNER JOIN $versionTable as version ON (version.workflow_id=workflow.id)
        WHERE version.id = (SELECT Max(id) FROM $versionTable WHERE workflow_id= version.workflow_id) AND workflow.status IN (%s)
        ORDER BY workflow.id DESC",
        implode(",", $status)
      ) :
      "SELECT workflow.*, version.id AS version_id, version.steps, version.trigger_keys
      FROM $workflowTable AS workflow INNER JOIN $versionTable as version ON (version.workflow_id=workflow.id)
      WHERE version.id = (SELECT Max(id) FROM $versionTable WHERE workflow_id= version.workflow_id)
      ORDER BY workflow.id DESC;";

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }

  public function getWorkflowCount(): int {
    $workflowTable = esc_sql($this->workflowTable);
    return (int)$this->wpdb->get_var("SELECT COUNT(*) FROM $workflowTable");
  }

  /** @return string[] */
  public function getActiveTriggerKeys(): array {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);
    $query = (string)$this->wpdb->prepare("
      SELECT DISTINCT version.trigger_keys
      FROM $workflowTable AS workflow, $versionTable as version
      WHERE workflow.status = %s AND workflow.id=version.workflow_id
      ORDER BY version.id DESC",
        Workflow::STATUS_ACTIVE
    );
    $result = $this->wpdb->get_col($query);

    $triggerKeys = [];
    foreach ($result as $item) {
      /** @var string[] $keys */
      $keys = Json::decode($item);
      $triggerKeys = array_merge($triggerKeys, $keys);
    }
    return array_unique($triggerKeys);
  }

  /** @return Workflow[] */
  public function getActiveWorkflowsByTrigger(Trigger $trigger): array {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);
    $query = (string)$this->wpdb->prepare("
      SELECT workflow.*, version.id AS version_id, version.steps, version.trigger_keys
      FROM $workflowTable AS workflow INNER JOIN $versionTable as version ON (version.workflow_id=workflow.id)
      WHERE workflow.status = %s AND version.trigger_keys LIKE %s AND version.id = (SELECT Max(id) FROM $versionTable WHERE workflow_id= version.workflow_id)",
        Workflow::STATUS_ACTIVE,
        '%' . $this->wpdb->esc_like($trigger->getKey()) . '%'
    );

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }

  public function deleteWorkflow(Workflow $workflow): void {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);
    $workflowRunTable = esc_sql($this->wpdb->prefix . 'mailpoet_workflow_runs');
    $workflowRunLogTable = esc_sql($this->wpdb->prefix . 'mailpoet_workflow_run_logs');
    $workflowId = $workflow->getId();
    $runLogsQuery = $this->wpdb->prepare(
      "
        DELETE FROM $workflowRunLogTable
        WHERE workflow_run_id IN (
          SELECT id FROM $workflowRunTable
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
    $versionsDeleted = $this->wpdb->delete($versionTable, ['workflow_id' => $workflowId]);
    if (!is_int($versionsDeleted)) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $workflowDeleted = $this->wpdb->delete($workflowTable, ['id' => $workflowId]);
    if (!is_int($workflowDeleted)) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function truncate(): bool {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);
    return $this->wpdb->query("truncate $workflowTable;") === true &&
      $this->wpdb->query("truncate $versionTable;") === true;
  }

  public function getNameColumnLength(): int {
    $nameColumnLengthInfo = $this->wpdb->get_col_length($this->workflowTable, 'name');
    return is_array($nameColumnLengthInfo)
      ? $nameColumnLengthInfo['length'] ?? 255
      : 255;
  }

  private function getWorkflowHeaderData(Workflow $workflow): array {
    $workflowHeader = $workflow->toArray();
    unset($workflowHeader['steps']);
    unset($workflowHeader['trigger_keys']);
    return $workflowHeader;
  }

  private function insertWorkflowVersion(int $workflowId, Workflow $workflow): void {
    $dateString = (new DateTimeImmutable())->format(DateTimeImmutable::W3C);
    $data = [
      'workflow_id' => $workflowId,
      'steps' => $workflow->toArray()['steps'],
      'trigger_keys' => $workflow->toArray()['trigger_keys'],
      'created_at' => $dateString,
      'updated_at' => $dateString,
    ];
    $result = $this->wpdb->insert($this->versionsTable, $data);
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }
}
