<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\WorkflowRunLog;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\InvalidStateException;
use wpdb;

class WorkflowRunLogStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_workflow_run_logs';
    $this->wpdb = $wpdb;
  }

  public function createWorkflowRunLog(WorkflowRunLog $workflowRunLog): int {
    $result = $this->wpdb->insert($this->table, $workflowRunLog->toArray());
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    return $this->wpdb->insert_id;
  }

  public function getWorkflowRunLog(int $id): ?WorkflowRunLog {
    $table = esc_sql($this->table);
    $query = $this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);

    if (!is_string($query)) {
      throw InvalidStateException::create();
    }

    $result = $this->wpdb->get_row($query, ARRAY_A);

    if ($result) {
      $data = (array)$result;
      return WorkflowRunLog::fromArray($data);
    }
    return null;
  }

  /**
   * @param int $workflowRunId
   * @return WorkflowRunLog[]
   * @throws InvalidStateException
   */
  public function getLogsForWorkflowRun(int $workflowRunId): array {
    $table = esc_sql($this->table);
    $query = $this->wpdb->prepare("
        SELECT *
        FROM $table
        WHERE workflow_run_id = %d
        ORDER BY id ASC
        ", $workflowRunId);

    if (!is_string($query)) {
      throw InvalidStateException::create();
    }

    $results = $this->wpdb->get_results($query, ARRAY_A);

    if (!is_array($results)) {
      throw InvalidStateException::create();
    }

    if ($results) {
      return array_map(function($data) {
        return WorkflowRunLog::fromArray($data);
      }, $results);
    }

    return [];
  }
}
