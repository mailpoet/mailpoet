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

  public function getAutomationStats(int $automationId, int $versionId = null, \DateTimeImmutable $after = null, \DateTimeImmutable $before = null): AutomationStatistics {
    $data = $this->getStatistics([$automationId], $versionId, $after, $before);
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
  private function getStatistics(array $automationIds, int $versionId = null, \DateTimeImmutable $after = null, \DateTimeImmutable $before = null): array {
    $totalSubquery = $this->getStatsQuery($automationIds, $versionId, $after, $before);
    $runningSubquery = $this->getStatsQuery($automationIds, $versionId, $after, $before, AutomationRun::STATUS_RUNNING);

    // The subqueries are created using $wpdb->prepare().
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The subqueries are created using $wpdb->prepare()
    // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter -- The subqueries are created using $wpdb->prepare()
    $results = (array)$this->wpdb->get_results("
      SELECT t.id, t.count AS total, r.count AS running
      FROM ($totalSubquery) t
      LEFT JOIN ($runningSubquery) r ON t.id = r.id
    ", ARRAY_A);
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    /** @var array{id: int, total: int, running: int} $results */
    return array_combine(array_column($results, 'id'), $results) ?: [];
  }

  private function getStatsQuery(array $automationIds, int $versionId = null, \DateTimeImmutable $after = null, \DateTimeImmutable $before = null, string $status = null): string {
    $placeholders = implode(',', array_fill(0, count($automationIds), '%d'));
    /** @var string $versionCondition */
    $versionCondition = $versionId ? $this->wpdb->prepare('AND version_id = %d', $versionId) : '';
    $versionCondition = strval($versionCondition);
    /** @var string $statusCondition */
    $statusCondition = $status ? $this->wpdb->prepare('AND status = %s', $status) : '';
    $statusCondition = strval($statusCondition);
    /** @var string $dateCondition */
    $dateCondition = $after !== null && $before !== null ? $this->wpdb->prepare('AND created_at BETWEEN %s AND %s', $after->format('Y-m-d H:i:s'), $before->format('Y-m-d H:i:s')) : '';
    $dateCondition = strval($dateCondition);
    /** @var literal-string $sql */
    $sql = "
      SELECT automation_id AS id, COUNT(*) AS count
      FROM %i
      WHERE automation_id IN ($placeholders)
      $versionCondition
      $statusCondition
      $dateCondition
      GROUP BY automation_id
    ";
    /** @var string $query */
    $query = $this->wpdb->prepare($sql, $this->table, ...$automationIds); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name is sanitized and dynamic values are prepared with placeholders
    return strval($query);
  }
}
