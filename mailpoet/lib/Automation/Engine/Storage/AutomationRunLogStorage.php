<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\InvalidStateException;
use wpdb;

class AutomationRunLogStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_run_logs';
    $this->wpdb = $wpdb;
  }

  public function createAutomationRunLog(AutomationRunLog $automationRunLog): int {
    $result = $this->wpdb->insert($this->table, $automationRunLog->toArray());
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    return $this->wpdb->insert_id;
  }

  public function getAutomationRunLog(int $id): ?AutomationRunLog {
    $table = esc_sql($this->table);
    $query = $this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);

    if (!is_string($query)) {
      throw InvalidStateException::create();
    }

    $result = $this->wpdb->get_row($query, ARRAY_A);

    if ($result) {
      $data = (array)$result;
      return AutomationRunLog::fromArray($data);
    }
    return null;
  }

  /**
   * @param int $automationRunId
   * @return AutomationRunLog[]
   * @throws InvalidStateException
   */
  public function getLogsForAutomationRun(int $automationRunId): array {
    $table = esc_sql($this->table);
    $query = $this->wpdb->prepare("
      SELECT *
      FROM $table
      WHERE automation_run_id = %d
      ORDER BY id ASC
    ", $automationRunId);

    if (!is_string($query)) {
      throw InvalidStateException::create();
    }

    $results = $this->wpdb->get_results($query, ARRAY_A);

    if (!is_array($results)) {
      throw InvalidStateException::create();
    }

    if ($results) {
      return array_map(function($data) {
        return AutomationRunLog::fromArray($data);
      }, $results);
    }

    return [];
  }

  public function truncate(): void {
    $table = esc_sql($this->table);
    $sql = "TRUNCATE $table";
    $this->wpdb->query($sql);
  }
}
