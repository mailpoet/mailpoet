<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationStatistics;

class AutomationStatisticsStorage {
  /** @var string */
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
      $emailStats = $this->getEmailStatistics($id);
      $statistics[$id] = new AutomationStatistics(
        $id,
        (int)($data[$id]['total'] ?? 0),
        (int)($data[$id]['running'] ?? 0),
        null,
        $emailStats['sent'],
        $emailStats['opened'],
        $emailStats['clicked'],
        $emailStats['orders'],
        $emailStats['revenue']
      );
    }
    return $statistics;
  }

  public function getAutomationStats(int $automationId, ?int $versionId = null, ?\DateTimeImmutable $after = null, ?\DateTimeImmutable $before = null): AutomationStatistics {
    $data = $this->getStatistics([$automationId], $versionId, $after, $before);
    $emailStats = $this->getEmailStatistics($automationId, $versionId, $after, $before);

    return new AutomationStatistics(
      $automationId,
      (int)($data[$automationId]['total'] ?? 0),
      (int)($data[$automationId]['running'] ?? 0),
      $versionId,
      $emailStats['sent'],
      $emailStats['opened'],
      $emailStats['clicked'],
      $emailStats['orders'],
      $emailStats['revenue']
    );
  }

  /**
   * @param int[] $automationIds
   * @return array<int, array{id: int, total: int, running: int}>
   */
  private function getStatistics(array $automationIds, ?int $versionId = null, ?\DateTimeImmutable $after = null, ?\DateTimeImmutable $before = null): array {
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

  private function getStatsQuery(array $automationIds, ?int $versionId = null, ?\DateTimeImmutable $after = null, ?\DateTimeImmutable $before = null, ?string $status = null): string {
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

  private function getAutomationEmailIds(int $automationId, ?int $versionId = null): array {
    global $wpdb;

    $versionsTable = $wpdb->prefix . 'mailpoet_automation_versions';

    if ($versionId) {
      $query = $wpdb->prepare(
        '
          SELECT steps
          FROM %i
          WHERE automation_id = %d
          AND version_id = %d
        ',
        [$versionsTable, $automationId, $versionId]
      );
    } else {
      $query = $wpdb->prepare(
        '
          SELECT steps
          FROM %i
          WHERE automation_id = %d
        ',
        [$versionsTable, $automationId]
      );
    }

    $results = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $emailIds = [];

    foreach ($results as $result) {
      $steps = json_decode($result['steps'], true);
      if (!is_array($steps)) {
        continue;
      }

      foreach ($steps as $step) {
        if (isset($step['key']) && $step['key'] === 'mailpoet:send-email' && isset($step['args']['email_id'])) {
          $emailIds[] = (int)$step['args']['email_id'];
        }
      }
    }

    return array_unique($emailIds);
  }

  private function queryEmailStatistics(array $emailIds, ?\DateTimeImmutable $after = null, ?\DateTimeImmutable $before = null): array {
    if (empty($emailIds)) {
      return ['sent' => 0, 'opened' => 0, 'clicked' => 0, 'orders' => 0, 'revenue' => 0.0];
    }

    $dateCondition = '';
    $dateParams = [];
    if ($after && $before) {
      $dateCondition = 'AND created_at BETWEEN %s AND %s';
      $dateParams = [$after->format('Y-m-d H:i:s'), $before->format('Y-m-d H:i:s')];
    } elseif ($after && $before === null) {
      $dateCondition = 'AND created_at >= %s';
      $dateParams = [$after->format('Y-m-d H:i:s')];
    } elseif ($after === null && $before) {
      $dateCondition = 'AND created_at <= %s';
      $dateParams = [$before->format('Y-m-d H:i:s')];
    }

    $sentCounts = $this->getEmailSentCounts($emailIds, $dateCondition, $dateParams);
    $openCounts = $this->getEmailOpenCounts($emailIds, $dateCondition, $dateParams);
    $clickCounts = $this->getEmailClickCounts($emailIds, $dateCondition, $dateParams);
    $revenueData = $this->getEmailRevenueCounts($emailIds, $dateCondition, $dateParams);

    // Sum all the per-email results
    $totalSent = array_sum($sentCounts);
    $totalOpened = array_sum($openCounts);
    $totalClicked = array_sum($clickCounts);
    $totalOrders = array_sum(array_column($revenueData, 'orders'));
    $totalRevenue = array_sum(array_column($revenueData, 'revenue'));

    return [
      'sent' => $totalSent,
      'opened' => $totalOpened,
      'clicked' => $totalClicked,
      'orders' => $totalOrders,
      'revenue' => $totalRevenue,
    ];
  }

  private function getEmailSentCounts(array $emailIds, string $dateCondition, array $dateParams): array {
    global $wpdb;

    if (empty($emailIds)) {
      return [];
    }

    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared -- The number of replacements is dynamic.
    $results = $wpdb->get_results(
      $wpdb->prepare(
        '
          SELECT sq.newsletter_id, SUM(sq.count_processed) as total
          FROM %i sq
          JOIN %i st ON sq.task_id = st.id
          WHERE sq.newsletter_id IN (' . implode(',', array_fill(0, count($emailIds), '%d')) . ')
          AND st.status = %s
          ' . ($dateCondition ? str_replace('created_at', 'sq.created_at', $dateCondition) : '') . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The condition uses placeholders. */ '
          GROUP BY sq.newsletter_id
        ',
        array_merge(
          [$wpdb->prefix . 'mailpoet_sending_queues', $wpdb->prefix . 'mailpoet_scheduled_tasks'],
          $emailIds,
          ['completed'],
          $dateParams
        )
      ),
      ARRAY_A
    );
    $counts = [];
    foreach ($results as $result) {
      $counts[(int)$result['newsletter_id']] = (int)$result['total'];
    }
    return $counts;
  }

  private function getEmailOpenCounts(array $emailIds, string $dateCondition, array $dateParams): array {
    global $wpdb;

    if (empty($emailIds)) {
      return [];
    }

    $query = $wpdb->prepare('
      SELECT newsletter_id, COUNT(DISTINCT subscriber_id) as total
      FROM %i
      WHERE newsletter_id IN (' . implode(',', array_fill(0, count($emailIds), '%d')) . ')
      AND ((user_agent_type = %d) OR (user_agent_type IS NULL))
      ' . $dateCondition /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The date condition uses placeholders. */ . '
      GROUP BY newsletter_id
    ', array_merge(
        [$wpdb->prefix . 'mailpoet_statistics_opens'],
        $emailIds,
        [0], // USER_AGENT_TYPE_HUMAN = 0
        $dateParams
      ));
    $results = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $counts = [];
    foreach ($results as $result) {
      $counts[(int)$result['newsletter_id']] = (int)$result['total'];
    }
    return $counts;
  }

  private function getEmailClickCounts(array $emailIds, string $dateCondition, array $dateParams): array {
    global $wpdb;

    if (empty($emailIds)) {
      return [];
    }

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic placeholders are used with prepare().
    $query = $wpdb->prepare('
      SELECT newsletter_id, COUNT(DISTINCT subscriber_id) as total
      FROM %i
      WHERE newsletter_id IN (' . implode(',', array_fill(0, count($emailIds), '%d')) . ')
      AND ((user_agent_type = %d) OR (user_agent_type IS NULL))
      ' . $dateCondition /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The date condition uses placeholders. */ . '
      GROUP BY newsletter_id
    ', array_merge(
        [$wpdb->prefix . 'mailpoet_statistics_clicks'],
        $emailIds,
        [0], // USER_AGENT_TYPE_HUMAN = 0
        $dateParams
      ));
    $results = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $counts = [];
    foreach ($results as $result) {
      $counts[(int)$result['newsletter_id']] = (int)$result['total'];
    }
    return $counts;
  }

  private function getEmailRevenueCounts(array $emailIds, string $dateCondition, array $dateParams): array {
    global $wpdb;

    // Check if WooCommerce is active before querying
    if (!function_exists('get_woocommerce_currency')) {
      return [];
    }

    if (empty($emailIds)) {
      return [];
    }

    $currency = get_woocommerce_currency();

    // Use the same purchase states as overview (defaults to ['completed'] but could be filtered)
    $purchaseStates = ['completed']; // Default value matching WCHelper::getPurchaseStates()
    if (function_exists('apply_filters')) {
      $purchaseStates = apply_filters('mailpoet_purchase_order_states', $purchaseStates);
    }

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic placeholders are used with prepare().
    $query = $wpdb->prepare('
      SELECT newsletter_id, COUNT(id) as orders, SUM(order_price_total) as revenue
      FROM %i
      WHERE newsletter_id IN (' . implode(',', array_fill(0, count($emailIds), '%d')) . ')
      AND status IN (' . implode(',', array_fill(0, count($purchaseStates), '%s')) . ')
      AND order_currency = %s
      ' . $dateCondition . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The date condition uses placeholders. */ '
      GROUP BY newsletter_id
    ', array_merge(
        [$wpdb->prefix . 'mailpoet_statistics_woocommerce_purchases'],
        $emailIds,
        $purchaseStates,
        [$currency],
        $dateParams
      ));
    $results = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $revenueData = [];
    foreach ($results as $result) {
      $revenueData[(int)$result['newsletter_id']] = [
        'orders' => (int)$result['orders'],
        'revenue' => (float)$result['revenue'],
      ];
    }
    return $revenueData;
  }

  private function getEmailStatistics(int $automationId, ?int $versionId = null, ?\DateTimeImmutable $after = null, ?\DateTimeImmutable $before = null): array {
    $emailIds = $this->getAutomationEmailIds($automationId, $versionId);
    return $this->queryEmailStatistics($emailIds, $after, $before);
  }
}
