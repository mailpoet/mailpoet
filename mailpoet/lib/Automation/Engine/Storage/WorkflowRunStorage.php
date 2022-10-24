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
   * @throws Exceptions\InvalidStateException
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

  public function updateStatus(string $status, int ...$ids): void {
    if (!$ids) {
      return;
    }
    $table = esc_sql($this->table);
    $ids = esc_sql(implode(',', $ids));
    $query = (string)$this->wpdb->prepare("UPDATE $table SET status = %s WHERE id IN ($ids)", $status);
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
