<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Utils\Json;
use MailPoet\Automation\Engine\Workflows\Trigger;
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
    $result = $this->wpdb->insert($this->workflowTable, $this->workflowHeaderData($workflow));
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $id = (int)$this->wpdb->insert_id;
    $this->insertWorkflowVersion($id, $workflow);
    return $id;
  }

  public function updateWorkflow(Workflow $workflow): void {
    $result = $this->wpdb->update($this->workflowTable, $this->workflowHeaderData($workflow), ['id' => $workflow->getId()]);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $this->insertWorkflowVersion($workflow->getId(), $workflow);
  }

  public function getWorkflow(int $workflowId, int $versionId = null): ?Workflow {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);

    $query = !$versionId ? (string)$this->wpdb->prepare("
SELECT
      workflow.*,
      version.id AS version_id,
      version.steps,
      version.trigger_keys
FROM
      $workflowTable as workflow,
      $versionTable as version
WHERE
      version.workflow_id = workflow.id AND
      workflow.id = %d
ORDER BY
      version.id DESC
LIMIT
      0,1;",
      $workflowId
    ) : (string)$this->wpdb->prepare("
SELECT
      workflow.*,
      version.id AS version_id,
      version.steps,
      version.trigger_keys
FROM
      $workflowTable as workflow,
      $versionTable as version
WHERE
      version.workflow_id = workflow.id AND
      version.id = %d",
      $versionId
    );
    $data = $this->wpdb->get_row($query, ARRAY_A);
    return $data ? Workflow::fromArray((array)$data) : null;
  }

  /** @return Workflow[] */
  public function getWorkflows(): array {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);
    $query = "
SELECT
      workflow.*,
      version.id AS version_id,
      version.steps,
      version.trigger_keys
FROM
      $workflowTable AS workflow
        LEFT JOIN
         $versionTable as version ON (version.workflow_id=workflow.id)
WHERE
      version.id = (SELECT Max(id) FROM $versionTable WHERE workflow_id= version.workflow_id)
ORDER BY
      workflow.id DESC; ";
    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }

  /** @return string[] */
  public function getActiveTriggerKeys(): array {
    $workflowTable = esc_sql($this->workflowTable);
    $versionTable = esc_sql($this->versionsTable);
    $query = (string)$this->wpdb->prepare(
        "
SELECT
    DISTINCT version.trigger_keys
FROM
     $workflowTable AS workflow,
     $versionTable as version
WHERE
     workflow.status = %s AND
     workflow.id=version.workflow_id
ORDER BY
     version.id DESC",
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
    $query = (string)$this->wpdb->prepare(
        "
SELECT
       workflow.*,
      version.id AS version_id,
      version.steps,
      version.trigger_keys
FROM
     $workflowTable AS workflow
        LEFT JOIN
         $versionTable as version ON (version.workflow_id=workflow.id)
WHERE
      workflow.status = %s AND
      version.trigger_keys LIKE %s AND
      version.id = (SELECT Max(id) FROM $versionTable WHERE workflow_id= version.workflow_id)
  ",
        Workflow::STATUS_ACTIVE,
        '%' . $this->wpdb->esc_like($trigger->getKey()) . '%'
    );

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }

  private function workflowHeaderData(Workflow $workflow): array {
    $workflowHeader = $workflow->toArray();
    unset($workflowHeader['steps']);
    unset($workflowHeader['trigger_keys']);
    return $workflowHeader;
  }

  private function insertWorkflowVersion(int $workflowId, Workflow $workflow): void {

    $data = [
      'workflow_id' => $workflowId,
      'steps' => $workflow->toArray()['steps'],
      'trigger_keys' => $workflow->toArray()['trigger_keys'],
    ];
    $result = $this->wpdb->insert($this->versionsTable, $data);
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }
}
