<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Exceptions;
use wpdb;

class AutomationRunStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_runs';
    $this->wpdb = $wpdb;
  }

  public function createAutomationRun(AutomationRun $automationRun): int {
    $result = $this->wpdb->insert($this->table, $automationRun->toArray());
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    return $this->wpdb->insert_id;
  }

  public function getAutomationRun(int $id): ?AutomationRun {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
    $result = $this->wpdb->get_row($query, ARRAY_A);
    return $result ? AutomationRun::fromArray((array)$result) : null;
  }

  /**
   * @param Automation $automation
   * @return AutomationRun[]
   */
  public function getAutomationRunsForAutomation(Automation $automation): array {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("SELECT * FROM $table WHERE automation_id = %d", $automation->getId());
    $result = $this->wpdb->get_results($query, ARRAY_A);
    return is_array($result) ? array_map(
      function(array $runData): AutomationRun {
        return AutomationRun::fromArray($runData);
      },
      $result
    ) : [];
  }

  public function getCountForAutomation(Automation $automation, string ...$status): int {
    if (!count($status)) {
      return 0;
    }

    $table = esc_sql($this->table);
    $statusSql = (string)$this->wpdb->prepare(implode(',', array_fill(0, count($status), '%s')), ...$status);
    $query = (string)$this->wpdb->prepare( "SELECT count(id) as count from $table where automation_id = %d and status IN ($statusSql)", $automation->getId());
    $result = $this->wpdb->get_col($query);
    return $result ? (int)current($result) : 0;
  }

  public function updateStatus(int $id, string $status): void {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("
      UPDATE $table
      SET status = %s, updated_at = current_timestamp()
      WHERE id = %d
    ", $status, $id);
    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function updateNextStep(int $id, ?string $nextStepId): void {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("
      UPDATE $table
      SET next_step_id = %s, updated_at = current_timestamp()
      WHERE id = %d
    ", $nextStepId, $id);
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
