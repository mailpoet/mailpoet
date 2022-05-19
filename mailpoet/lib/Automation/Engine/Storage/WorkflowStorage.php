<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Utils\Json;
use MailPoet\Automation\Engine\Workflows\Trigger;
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

  public function getWorkflow(int $id): ?Workflow {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
    $data = $this->wpdb->get_row($query, ARRAY_A);
    return $data ? Workflow::fromArray((array)$data) : null;
  }

  /** @return Workflow[] */
  public function getWorkflows(): array {
    $table = esc_sql($this->table);
    $query = "SELECT * FROM $table ORDER BY id DESC";
    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }

  /** @return string[] */
  public function getActiveTriggerKeys(): array {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare(
        "SELECT DISTINCT trigger_keys FROM $table WHERE status = %s",
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
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare(
        "SELECT * FROM $table WHERE status = %s AND trigger_keys LIKE %s",
        Workflow::STATUS_ACTIVE,
        '%' . $this->wpdb->esc_like($trigger->getKey()) . '%'
    );

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $workflowData) {
      return Workflow::fromArray($workflowData);
    }, (array)$data);
  }
}
