<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Integration\Trigger;
use wpdb;

class AutomationStorage {
  /** @var string */
  private $automationsTable;

  /** @var string */
  private $versionsTable;

  /** @var string */
  private $triggersTable;

  /** @var string */
  private $runsTable;

  /** @var wpdb */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->automationsTable = $wpdb->prefix . 'mailpoet_automations';
    $this->versionsTable = $wpdb->prefix . 'mailpoet_automation_versions';
    $this->triggersTable = $wpdb->prefix . 'mailpoet_automation_triggers';
    $this->runsTable = $wpdb->prefix . 'mailpoet_automation_runs';
    $this->wpdb = $wpdb;
  }

  public function createAutomation(Automation $automation): int {
    $automationHeaderData = $this->getAutomationHeaderData($automation);
    unset($automationHeaderData['id']);
    $result = $this->wpdb->insert($this->automationsTable, $automationHeaderData);
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $id = $this->wpdb->insert_id;
    $this->insertAutomationVersion($id, $automation);
    $this->insertAutomationTriggers($id, $automation);
    return $id;
  }

  public function updateAutomation(Automation $automation): void {
    $oldRecord = $this->getAutomation($automation->getId());
    if ($oldRecord && $oldRecord->equals($automation)) {
      return;
    }
    $result = $this->wpdb->update($this->automationsTable, $this->getAutomationHeaderData($automation), ['id' => $automation->getId()]);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    $this->insertAutomationVersion($automation->getId(), $automation);
    $this->insertAutomationTriggers($automation->getId(), $automation);
  }

  public function getAutomation(int $automationId, int $versionId = null): ?Automation {
    $automationsTable = esc_sql($this->automationsTable);
    $versionsTable = esc_sql($this->versionsTable);

    $query = !$versionId ? (string)$this->wpdb->prepare(
      "
        SELECT a.*, v.id AS version_id, v.steps
        FROM $automationsTable as a, $versionsTable as v
        WHERE v.automation_id = a.id AND a.id = %d
        ORDER BY v.id DESC
        LIMIT 1
      ",
      $automationId
    ) : (string)$this->wpdb->prepare(
      "
        SELECT a.*, v.id AS version_id, v.steps
        FROM $automationsTable as a, $versionsTable as v
        WHERE v.automation_id = a.id AND v.id = %d
      ",
      $versionId
    );
    $data = $this->wpdb->get_row($query, ARRAY_A);
    return $data ? Automation::fromArray((array)$data) : null;
  }

  /** @return Automation[] */
  public function getAutomations(array $status = null): array {
    $automationsTable = esc_sql($this->automationsTable);
    $versionsTable = esc_sql($this->versionsTable);
    $query = $status ?
      (string)$this->wpdb->prepare("
        SELECT a.*, v.id AS version_id, v.steps
        FROM $automationsTable AS a
        INNER JOIN $versionsTable as v ON (v.automation_id = a.id)
        WHERE v.id = (
          SELECT MAX(id) FROM $versionsTable WHERE automation_id = v.automation_id
        )
        AND a.status IN (%s)
        ORDER BY a.id DESC",
        implode(",", $status)
      ) : "
        SELECT a.*, v.id AS version_id, v.steps
        FROM $automationsTable AS a
        INNER JOIN $versionsTable as v ON (v.automation_id = a.id)
        WHERE v.id = (
          SELECT MAX(id) FROM $versionsTable WHERE automation_id = v.automation_id
        )
        ORDER BY a.id DESC
      ";

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $automationData) {
      return Automation::fromArray($automationData);
    }, (array)$data);
  }

  public function getAutomationCount(): int {
    $automationsTable = esc_sql($this->automationsTable);
    return (int)$this->wpdb->get_var("SELECT COUNT(*) FROM $automationsTable");
  }

  /** @return string[] */
  public function getActiveTriggerKeys(): array {
    $automationsTable = esc_sql($this->automationsTable);
    $triggersTable = esc_sql($this->triggersTable);

    $query = (string)$this->wpdb->prepare(
      "
        SELECT DISTINCT t.trigger_key
        FROM {$automationsTable} AS a
        JOIN $triggersTable as t
        WHERE a.status = %s AND a.id = t.automation_id
        ORDER BY trigger_key DESC
      ",
      Automation::STATUS_ACTIVE
    );
    return $this->wpdb->get_col($query);
  }

  /** @return Automation[] */
  public function getActiveAutomationsByTrigger(Trigger $trigger): array {
    $automationsTable = esc_sql($this->automationsTable);
    $versionsTable = esc_sql($this->versionsTable);
    $triggersTable = esc_sql($this->triggersTable);

    $query = (string)$this->wpdb->prepare(
      "
        SELECT a.*, v.id AS version_id, v.steps
        FROM $automationsTable AS a
        INNER JOIN $triggersTable as t ON (t.automation_id = a.id)
        INNER JOIN $versionsTable as v ON (v.automation_id = a.id)
        WHERE a.status = %s
        AND t.trigger_key = %s
        AND v.id = (
          SELECT MAX(id) FROM $versionsTable WHERE automation_id = v.automation_id
        )
      ",
      Automation::STATUS_ACTIVE,
      $trigger->getKey()
    );

    $data = $this->wpdb->get_results($query, ARRAY_A);
    return array_map(function (array $automationData) {
      return Automation::fromArray($automationData);
    }, (array)$data);
  }

  public function getCountOfActiveByTriggerKeysAndAction(array $triggerKeys, string $actionKey): int {
    $automationsTable = esc_sql($this->automationsTable);
    $versionsTable = esc_sql($this->versionsTable);
    $triggersTable = esc_sql($this->triggersTable);

    $triggerKeysPlaceholders = implode(',', array_fill(0, count($triggerKeys), '%s'));
    $queryArgs = array_merge(
      $triggerKeys,
      [
        Automation::STATUS_ACTIVE,
        '%"' . $this->wpdb->esc_like($actionKey) . '"%',
      ]
    );
    // Using the phpcs:ignore because the query arguments count is dynamic and passed via an array but the code sniffer sees only one argument
    $query = (string)$this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
      "
        SELECT count(*)
        FROM $automationsTable AS a
        INNER JOIN $triggersTable as t ON (t.automation_id = a.id) AND t.trigger_key IN ({$triggerKeysPlaceholders})
        INNER JOIN $versionsTable as v ON v.id = (SELECT MAX(id) FROM $versionsTable WHERE automation_id = a.id)
        WHERE a.status = %s
        AND v.steps LIKE %s
      ",
      $queryArgs
    );

    return (int)$this->wpdb->get_var($query);
  }

  public function deleteAutomation(Automation $automation): void {
    $automationRunsTable = esc_sql($this->runsTable);
    $automationRunLogsTable = esc_sql($this->wpdb->prefix . 'mailpoet_automation_run_logs');
    $automationId = $automation->getId();
    $runLogsQuery = (string)$this->wpdb->prepare(
      "
        DELETE FROM $automationRunLogsTable
        WHERE automation_run_id IN (
          SELECT id
          FROM $automationRunsTable
          WHERE automation_id = %d
        )
      ",
      $automationId
    );

    $logsDeleted = $this->wpdb->query($runLogsQuery);
    if ($logsDeleted === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    $runsDeleted = $this->wpdb->delete($this->runsTable, ['automation_id' => $automationId]);
    if ($runsDeleted === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    $versionsDeleted = $this->wpdb->delete($this->versionsTable, ['automation_id' => $automationId]);
    if ($versionsDeleted === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    $triggersDeleted = $this->wpdb->delete($this->triggersTable, ['automation_id' => $automationId]);
    if ($triggersDeleted === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    $automationDeleted = $this->wpdb->delete($this->automationsTable, ['id' => $automationId]);
    if ($automationDeleted === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function truncate(): void {
    $automationsTable = esc_sql($this->automationsTable);
    $result = $this->wpdb->query("TRUNCATE {$automationsTable}");
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    $versionsTable = esc_sql($this->versionsTable);
    $result = $this->wpdb->query("TRUNCATE {$versionsTable}");
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }

    $triggersTable = esc_sql($this->triggersTable);
    $result = $this->wpdb->query("TRUNCATE {$triggersTable}");
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  public function getNameColumnLength(): int {
    $nameColumnLengthInfo = $this->wpdb->get_col_length($this->automationsTable, 'name');
    return is_array($nameColumnLengthInfo)
      ? $nameColumnLengthInfo['length'] ?? 255
      : 255;
  }

  private function getAutomationHeaderData(Automation $automation): array {
    $automationHeader = $automation->toArray();
    unset($automationHeader['steps']);
    return $automationHeader;
  }

  private function insertAutomationVersion(int $automationId, Automation $automation): void {
    $dateString = (new DateTimeImmutable())->format(DateTimeImmutable::W3C);
    $data = [
      'automation_id' => $automationId,
      'steps' => $automation->toArray()['steps'],
      'created_at' => $dateString,
      'updated_at' => $dateString,
    ];
    $result = $this->wpdb->insert($this->versionsTable, $data);
    if (!$result) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }

  private function insertAutomationTriggers(int $automationId, Automation $automation): void {
    $triggerKeys = [];
    foreach ($automation->getSteps() as $step) {
      if ($step->getType() === Step::TYPE_TRIGGER) {
        $triggerKeys[] = $step->getKey();
      }
    }

    $triggersTable = esc_sql($this->triggersTable);

    // insert/update
    if ($triggerKeys) {
      $placeholders = implode(',', array_fill(0, count($triggerKeys), '(%d, %s)'));
      $query = (string)$this->wpdb->prepare(
        "INSERT IGNORE INTO {$triggersTable} (automation_id, trigger_key) VALUES {$placeholders}",
        array_merge(
          ...array_map(function (string $key) use ($automationId) {
            return [$automationId, $key];
          }, $triggerKeys)
        )
      );

      $result = $this->wpdb->query($query);
      if ($result === false) {
        throw Exceptions::databaseError($this->wpdb->last_error);
      }
    }

    // delete
    $placeholders = implode(',', array_fill(0, count($triggerKeys), '%s'));
    $query = $triggerKeys
      ? (string)$this->wpdb->prepare(
        "DELETE FROM {$triggersTable} WHERE automation_id = %d AND trigger_key NOT IN ({$placeholders})",
        array_merge([$automationId], $triggerKeys)
      )
      : (string)$this->wpdb->prepare("DELETE FROM {$triggersTable} WHERE automation_id = %d", $automationId);

    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }
}
