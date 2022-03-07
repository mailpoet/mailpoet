<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use wpdb;

class WorkflowRunStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_workflow_runs';
    $this->wpdb = $wpdb;
  }

  public function createWorkflowRun(WorkflowRun $workflowRun): int {
    $result = $this->wpdb->insert($this->table, $workflowRun->toArray());
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    return $this->wpdb->insert_id;
  }
}
