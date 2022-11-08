<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Exceptions;
use wpdb;

class WorkflowRunStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_workflow_runs';
    $this->wpdb = $wpdb;
  }

  public function createWorkflowRun(WorkflowRun $workflowRun): int {
    $result = $this->wpdb->insert($this->table, $workflowRun->toArray());
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    return $this->wpdb->insert_id;
  }

  public function getWorkflowRun(int $id): ?WorkflowRun {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
    $result = $this->wpdb->get_row($query, ARRAY_A);
    return $result ? WorkflowRun::fromArray((array)$result) : null;
  }

  /**
   * @param Workflow $workflow
   * @return WorkflowRun[]
   */
  public function getWorkflowRunsForWorkflow(Workflow $workflow): array {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("SELECT * FROM $table WHERE workflow_id = %d", $workflow->getId());
    $result = $this->wpdb->get_results($query, ARRAY_A);
    return is_array($result) ? array_map(
      function(array $runData): WorkflowRun {
        return WorkflowRun::fromArray($runData);
      },
      $result
    ) : [];
  }

  public function getCountForWorkflow(Workflow $workflow, string ...$status): int {
    if (!count($status)) {
      return 0;
    }

    $table = esc_sql($this->table);
    $statusSql = (string)$this->wpdb->prepare(implode(',', array_fill(0, count($status), '%s')), ...$status);
    $query = (string)$this->wpdb->prepare( "SELECT count(id) as count from $table where workflow_id = %d and status IN ($statusSql)", $workflow->getId());
    $result = $this->wpdb->get_col($query);
    return $result ? (int)current($result) : 0;
  }

  public function updateStatus(int $id, string $status): void {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("UPDATE $table SET status = %s WHERE id = %d", $status, $id);
    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function updateNextStep(int $id, ?string $nextStepId): void {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("UPDATE $table SET next_step_id = %s WHERE id = %d", $nextStepId, $id);
    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function truncate(): void {
    $table = esc_sql($this->table);
    $this->wpdb->query("truncate $table");
  }
}
