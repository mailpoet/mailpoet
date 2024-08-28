<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationStatistics;

class AutomationStatisticsStorage {
  /** @var string  */
  private $table;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_runs';
  }

  /** @return AutomationStatistics[] */
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
    global $wpdb;
    $totalSubquery = $this->getStatsQuery($automationIds, $versionId, $after, $before);
    $runningSubquery = $this->getStatsQuery($automationIds, $versionId, $after, $before, AutomationRun::STATUS_RUNNING);

    $results = (array)$wpdb->get_results(
      '
        SELECT t.id, t.count AS total, r.count AS running
        FROM (' . $totalSubquery /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The subquery was already prepared. */ . ') t
        LEFT JOIN (' . $runningSubquery /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The subquery was already prepared. */ . ') r ON t.id = r.id
      ',
      ARRAY_A
    );

    /** @var array{id: int, total: int, running: int} $results */
    return array_combine(array_column($results, 'id'), $results) ?: [];
  }

  private function getStatsQuery(array $automationIds, int $versionId = null, \DateTimeImmutable $after = null, \DateTimeImmutable $before = null, string $status = null): string {
    global $wpdb;

    $versionCondition = $versionId ? 'AND version_id = %d' : '';
    $statusCondition = $status ? 'AND status = %s' : '';
    $dateCondition = $after !== null && $before !== null ? 'AND created_at BETWEEN %s AND %s' : '';

    $coditions = "$versionCondition $statusCondition $dateCondition";
    $query = $wpdb->prepare(
      '
        SELECT automation_id AS id, COUNT(*) AS count
        FROM %i
        WHERE automation_id IN (' . implode(',', array_fill(0, count($automationIds), '%d')) . ')
        ' . $coditions . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The conditions use placeholders. */ '
        GROUP BY automation_id
      ',
      array_merge(
        [$this->table],
        $automationIds,
        $versionId ? [$versionId] : [],
        $status ? [$status] : [],
        $after !== null && $before !== null ? [$after->format('Y-m-d H:i:s'), $before->format('Y-m-d H:i:s')] : []
      )
    );
    return strval($query);
  }
}
