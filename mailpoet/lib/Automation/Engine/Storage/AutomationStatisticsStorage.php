<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationStatistics;

class AutomationStatisticsStorage {

  /** @var string  */
  private $table;

  /** @var \wpdb  */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_runs';
    $this->wpdb = $wpdb;
  }

  /**
   * @param Automation ...$automations
   * @return AutomationStatistics[]
   */
  public function getAutomationStatisticsForAutomations(Automation ...$automations): array {
    if (empty($automations)) {
      return [];
    }
    $automationIds = array_map(
      function(Automation $automation): int {
        return $automation->getId();
      },
      $automations
    );

    $data = $this->getStatistics($automationIds);
    $statistics = [];
    foreach ($automationIds as $id) {
      $statistics[$id] = new AutomationStatistics(
        $id,
        (int)($data[$id]['total'] ?? 0),
        (int)($data[$id]['running'] ?? 0)
      );
    }
    return $statistics;
  }

  public function getAutomationStats(int $automationId, int $versionId = null): AutomationStatistics {
    $data = $this->getStatistics([$automationId], $versionId);
    return new AutomationStatistics(
      $automationId,
      (int)($data[$automationId]['total'] ?? 0),
      (int)($data[$automationId]['running'] ?? 0),
      $versionId
    );
  }

  /**
   * @param int[] $automationIds
   * @return array<int, array{id: int, total: int, running: int}>
   */
  private function getStatistics(array $automationIds, int $versionId = null): array {
    $totalSubquery = $this->getStatsQuery($automationIds, $versionId);
    $runningSubquery = $this->getStatsQuery($automationIds, $versionId, AutomationRun::STATUS_RUNNING);

    // The subqueries are created using $wpdb->prepare().
    // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
    $results = (array)$this->wpdb->get_results("
      SELECT t.id, t.count AS total, r.count AS running
      FROM ($totalSubquery) t
      LEFT JOIN ($runningSubquery) r ON t.id = r.id
    ", ARRAY_A);

    return array_combine(array_column($results, 'id'), $results) ?: [];
  }

  private function getStatsQuery(array $automationIds, int $versionId = null, string $status = null): string {
    $table = esc_sql($this->table);
    $placeholders = implode(',', array_fill(0, count($automationIds), '%d'));
    $versionCondition = strval($versionId ? $this->wpdb->prepare('AND version_id = %d', $versionId) : '');
    $statusCondition = strval($status ? $this->wpdb->prepare('AND status = %s', $status) : '');
    $query = $this->wpdb->prepare("
      SELECT automation_id AS id, COUNT(*) AS count
      FROM $table
      WHERE automation_id IN ($placeholders)
      $versionCondition
      $statusCondition
      GROUP BY automation_id
    ", $automationIds);
    return strval($query);
  }
}
