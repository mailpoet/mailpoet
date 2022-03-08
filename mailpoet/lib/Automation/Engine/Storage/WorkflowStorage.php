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

  /** @return string[] */
  public function getActiveTriggerKeys(): array {
    $query = strval(
      $this->wpdb->prepare(
        "SELECT DISTINCT trigger_keys FROM $this->table WHERE status = %s",
        Workflow::STATUS_ACTIVE
      )
    );
    $result = $this->wpdb->get_col($query);

    $triggerKeys = [];
    foreach ($result as $item) {
      /** @var string[] $keys */
      $keys = (array)json_decode($item, true);
      $triggerKeys = array_merge($triggerKeys, $keys);
    }
    return array_unique($triggerKeys);
  }
}
