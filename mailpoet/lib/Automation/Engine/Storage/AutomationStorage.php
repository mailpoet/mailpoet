<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Integration\Trigger;

/**
 * @phpstan-type VersionDate array{id: int, created_at: \DateTimeImmutable}
 */
class AutomationStorage {
  /** @var string */
  private $automationsTable;

  /** @var string */
  private $versionsTable;

  /** @var string */
  private $triggersTable;

  /** @var string */
  private $runsTable;

  /** @var string */
  private $subjectsTable;

  public function __construct() {
    global $wpdb;
    $this->automationsTable = $wpdb->prefix . 'mailpoet_automations';
    $this->versionsTable = $wpdb->prefix . 'mailpoet_automation_versions';
    $this->triggersTable = $wpdb->prefix . 'mailpoet_automation_triggers';
    $this->runsTable = $wpdb->prefix . 'mailpoet_automation_runs';
    $this->subjectsTable = $wpdb->prefix . 'mailpoet_automation_run_subjects';
  }

  public function createAutomation(Automation $automation): int {
    global $wpdb;
    $automationHeaderData = $this->getAutomationHeaderData($automation);
    unset($automationHeaderData['id']);
    $result = $wpdb->insert($this->automationsTable, $automationHeaderData);
    if (!$result) {
      $this->throwDatabaseError();
    }
    $id = $wpdb->insert_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->insertAutomationVersion($id, $automation);
    $this->insertAutomationTriggers($id, $automation);
    return $id;
  }

  public function updateAutomation(Automation $automation): void {
    global $wpdb;
    $oldRecord = $this->getAutomation($automation->getId());
    if ($oldRecord && $oldRecord->equals($automation)) {
      return;
    }
    $result = $wpdb->update($this->automationsTable, $this->getAutomationHeaderData($automation), ['id' => $automation->getId()]);
    if ($result === false) {
      $this->throwDatabaseError();
    }
    $this->insertAutomationVersion($automation->getId(), $automation);
    $this->insertAutomationTriggers($automation->getId(), $automation);
  }

  public function getAutomationVersionDates(int $automationId): array {
    global $wpdb;

    $data = $wpdb->get_results(
      $wpdb->prepare(
        'SELECT id, created_at FROM %i WHERE automation_id = %d ORDER BY id DESC',
        $this->versionsTable,
        $automationId
      ),
      ARRAY_A
    );

    return is_array($data) ? array_map(
      function($row): array {
        /** @var array{id: string, created_at: string} $row */
        return [
          'id' => absint($row['id']),
          'created_at' => new \DateTimeImmutable($row['created_at']),
        ];
      },
      $data
    ) : [];
  }

  /**
   * @param int[] $versionIds
   * @return Automation[]
   */
  public function getAutomationWithDifferentVersions(array $versionIds): array {
    global $wpdb;
    if (!$versionIds) {
      return [];
    }

    $versionIds = array_map('intval', $versionIds);
    $data = $wpdb->get_results(
      $wpdb->prepare(
        '
          SELECT a.*, v.id AS version_id, v.steps
          FROM %i as a, %i as v
          WHERE v.automation_id = a.id AND v.id IN (' . implode(',', array_fill(0, count($versionIds), '%d')) . ')
          ORDER BY v.id DESC
        ',
        array_merge(
          [
            $this->automationsTable,
            $this->versionsTable,
          ],
          $versionIds
        )
      ),
      ARRAY_A
    );
    return is_array($data) ? array_map(
      function($row): Automation {
        return Automation::fromArray((array)$row);
      },
      $data
    ) : [];
  }

  public function getAutomation(int $automationId, int $versionId = null): ?Automation {
    global $wpdb;

    if ($versionId) {
      $automations = $this->getAutomationWithDifferentVersions([$versionId]);
      return $automations ? $automations[0] : null;
    }

    $data = $wpdb->get_row(
      $wpdb->prepare(
        '
          SELECT a.*, v.id AS version_id, v.steps
          FROM %i as a, %i as v
          WHERE v.automation_id = a.id AND a.id = %d
          ORDER BY v.id DESC
          LIMIT 1
        ',
        $this->automationsTable,
        $this->versionsTable,
        $automationId
      ),
      ARRAY_A
    );
    return $data ? Automation::fromArray((array)$data) : null;
  }

  /** @return Automation[] */
  public function getAutomations(array $status = null): array {
    global $wpdb;

    $statusFilter = $status ? 'AND a.status IN (' . implode(',', array_fill(0, count($status), '%s')) . ')' : '';
    $data = $wpdb->get_results(
      // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The number of replacements is dynamic.
      $wpdb->prepare(
        '
          SELECT a.*, v.id AS version_id, v.steps
          FROM %i AS a
          INNER JOIN %i as v ON (v.automation_id = a.id)
          WHERE v.id = (
            SELECT MAX(id) FROM %i WHERE automation_id = v.automation_id
          )
          ' . $statusFilter . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The condition uses placeholders. */ '
          ORDER BY a.id DESC
        ',
        $this->automationsTable,
        $this->versionsTable,
        $this->versionsTable,
        ...($status ?? []),
      ),
      ARRAY_A
    );
    return array_map(function ($automationData) {
      /** @var array $automationData - for PHPStan because it conflicts with expected callable(mixed): mixed)|null */
      return Automation::fromArray($automationData);
    }, (array)$data);
  }

  /** @return int[] */
  public function getAutomationIdsBySubject(Subject $subject, array $runStatus = null, int $inTheLastSeconds = null): array {
    global $wpdb;

    $statusFilter = $runStatus ? 'AND r.status IN (' . implode(',', array_fill(0, count($runStatus), '%s')) . ')' : '';
    $inTheLastFilter = isset($inTheLastSeconds) ? 'AND r.created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)' : '';

    $result = $wpdb->get_col(
      // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The number of replacements is dynamic.
      $wpdb->prepare(
        '
          SELECT DISTINCT a.id
          FROM %i a
          INNER JOIN %i r ON r.automation_id = a.id
          INNER JOIN %i s ON s.automation_run_id = r.id
          WHERE s.hash = %s
          ' . $statusFilter . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The condition uses placeholders. */ '
          ' . $inTheLastFilter . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The condition uses placeholders. */ '
          ORDER BY a.id DESC
        ',
        array_merge(
          [
            $this->automationsTable,
            $this->runsTable,
            $this->subjectsTable,
            $subject->getHash(),
          ],
          $runStatus ?? [],
          $inTheLastSeconds ? [$inTheLastSeconds] : []
        )
      )
    );
    return array_map('intval', $result);
  }

  public function getAutomationCount(): int {
    global $wpdb;
    return (int)$wpdb->get_var(
      $wpdb->prepare('SELECT COUNT(*) FROM %i', $this->automationsTable)
    );
  }

  /** @return string[] */
  public function getActiveTriggerKeys(): array {
    global $wpdb;
    return $wpdb->get_col(
      $wpdb->prepare(
        '
          SELECT DISTINCT t.trigger_key
          FROM %i AS a
          JOIN %i as t
          WHERE a.status = %s AND a.id = t.automation_id
          ORDER BY trigger_key DESC
        ',
        $this->automationsTable,
        $this->triggersTable,
        Automation::STATUS_ACTIVE
      )
    );
  }

  /** @return Automation[] */
  public function getActiveAutomationsByTrigger(Trigger $trigger): array {
    return $this->getActiveAutomationsByTriggerKey($trigger->getKey());
  }

  public function getActiveAutomationsByTriggerKey(string $triggerKey): array {
    global $wpdb;
    $data = $wpdb->get_results(
      $wpdb->prepare(
        '
          SELECT a.*, v.id AS version_id, v.steps
          FROM %i AS a
          INNER JOIN %i as t ON (t.automation_id = a.id)
          INNER JOIN %i as v ON (v.automation_id = a.id)
          WHERE a.status = %s
          AND t.trigger_key = %s
          AND v.id = (
            SELECT MAX(id) FROM %i WHERE automation_id = v.automation_id
          )
        ',
        $this->automationsTable,
        $this->triggersTable,
        $this->versionsTable,
        Automation::STATUS_ACTIVE,
        $triggerKey,
        $this->versionsTable
      ),
      ARRAY_A
    );
    return array_map(function ($automationData) {
      /** @var array $automationData - for PHPStan because it conflicts with expected callable(mixed): mixed)|null */
      return Automation::fromArray($automationData);
    }, (array)$data);
  }

  public function getCountOfActiveByTriggerKeysAndAction(array $triggerKeys, string $actionKey): int {
    global $wpdb;
    return (int)$wpdb->get_var(
      $wpdb->prepare(
        '
          SELECT COUNT(*)
          FROM %i AS a
          INNER JOIN %i as t ON (t.automation_id = a.id) AND t.trigger_key IN (' . implode(',', array_fill(0, count($triggerKeys), '%s')) . ')
          INNER JOIN %i as v ON v.id = (SELECT MAX(id) FROM %i WHERE automation_id = a.id)
          WHERE a.status = %s
          AND v.steps LIKE %s
        ',
        array_merge(
          [
            $this->automationsTable,
            $this->triggersTable,
          ],
          $triggerKeys,
          [
            $this->versionsTable,
            $this->versionsTable,
            Automation::STATUS_ACTIVE,
            '%"' . $wpdb->esc_like($actionKey) . '"%',
          ]
        )
      )
    );
  }

  public function deleteAutomation(Automation $automation): void {
    global $wpdb;
    $automationId = $automation->getId();
    $logsDeleted = $wpdb->query(
      $wpdb->prepare(
        '
          DELETE FROM %i
          WHERE automation_run_id IN (
            SELECT id
            FROM %i
            WHERE automation_id = %d
          )
        ',
        $wpdb->prefix . 'mailpoet_automation_run_logs',
        $this->runsTable,
        $automationId
      )
    );
    if ($logsDeleted === false) {
      $this->throwDatabaseError();
    }

    $runsDeleted = $wpdb->delete($this->runsTable, ['automation_id' => $automationId]);
    if ($runsDeleted === false) {
      $this->throwDatabaseError();
    }

    $versionsDeleted = $wpdb->delete($this->versionsTable, ['automation_id' => $automationId]);
    if ($versionsDeleted === false) {
      $this->throwDatabaseError();
    }

    $triggersDeleted = $wpdb->delete($this->triggersTable, ['automation_id' => $automationId]);
    if ($triggersDeleted === false) {
      $this->throwDatabaseError();
    }

    $automationDeleted = $wpdb->delete($this->automationsTable, ['id' => $automationId]);
    if ($automationDeleted === false) {
      $this->throwDatabaseError();
    }
  }

  public function truncate(): void {
    global $wpdb;
    $result = $wpdb->query($wpdb->prepare('TRUNCATE %i', $this->automationsTable));
    if ($result === false) {
      $this->throwDatabaseError();
    }

    $result = $wpdb->query($wpdb->prepare('TRUNCATE %i', $this->versionsTable));
    if ($result === false) {
      $this->throwDatabaseError();
    }

    $result = $wpdb->query($wpdb->prepare('TRUNCATE %i', $this->triggersTable));
    if ($result === false) {
      $this->throwDatabaseError();
    }
  }

  public function getNameColumnLength(): int {
    global $wpdb;
    $nameColumnLengthInfo = $wpdb->get_col_length($this->automationsTable, 'name');
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
    global $wpdb;
    $dateString = (new DateTimeImmutable())->format(DateTimeImmutable::W3C);
    $data = [
      'automation_id' => $automationId,
      'steps' => $automation->toArray()['steps'],
      'created_at' => $dateString,
      'updated_at' => $dateString,
    ];
    $result = $wpdb->insert($this->versionsTable, $data);
    if (!$result) {
      $this->throwDatabaseError();
    }
  }

  private function insertAutomationTriggers(int $automationId, Automation $automation): void {
    global $wpdb;
    $triggerKeys = [];
    foreach ($automation->getSteps() as $step) {
      if ($step->getType() === Step::TYPE_TRIGGER) {
        $triggerKeys[] = $step->getKey();
      }
    }

    // insert/update
    if ($triggerKeys) {
      $result = $wpdb->query(
        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The number of replacements is dynamic.
        $wpdb->prepare(
          'INSERT IGNORE INTO %i (automation_id, trigger_key) VALUES ' . implode(',', array_fill(0, count($triggerKeys), '(%d, %s)')),
          array_merge(
            [$this->triggersTable],
            ...array_map(function (string $key) use ($automationId) {
              return [$automationId, $key];
            }, $triggerKeys)
          )
        )
      );
      if ($result === false) {
        $this->throwDatabaseError();
      }
    }

    // delete
    if ($triggerKeys) {
      $result = $wpdb->query(
        $wpdb->prepare(
          'DELETE FROM %i WHERE automation_id = %d AND trigger_key NOT IN (' . implode(',', array_fill(0, count($triggerKeys), '%s')) . ')',
          array_merge(
            [
              $this->triggersTable,
              $automationId,
            ],
            $triggerKeys
          ),
        )
      );
    } else {
      $result = $wpdb->query(
        $wpdb->prepare(
          'DELETE FROM %i WHERE automation_id = %d',
          $this->triggersTable,
          $automationId
        )
      );
    }
    if ($result === false) {
      $this->throwDatabaseError();
    }
  }

  private function throwDatabaseError(): void {
    global $wpdb;
    throw Exceptions::databaseError($wpdb->last_error); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }
}
