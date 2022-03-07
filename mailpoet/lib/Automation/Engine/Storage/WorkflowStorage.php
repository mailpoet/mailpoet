<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Workflows\Workflow;
use wpdb;

class WorkflowStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_workflows';
    $this->wpdb = $wpdb;
  }

  public function createWorkflow(Workflow $workflow): int {
    $result = $this->wpdb->insert($this->table, $workflow->toArray());
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    return $this->wpdb->insert_id;
  }
}
