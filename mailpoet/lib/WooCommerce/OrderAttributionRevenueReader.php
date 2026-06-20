<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\WP\Functions as WPFunctions;
use WC_Order;

/**
 * @phpstan-type OrderRow array{created_at: string, newsletter_id: int, order_id: int, total: float, subscriber_id: int, first_name:string, last_name:string, email:string, subject:string, status:string}
 * @phpstan-type AttributionRow array{order_id: int, date_created_gmt: string, newsletter_id: string, subscriber_id: string|null, queue_id: string|null}
 */
class OrderAttributionRevenueReader {
  /** @var FeaturesController */
  private $featuresController;

  /** @var Helper */
  private $wooHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    FeaturesController $featuresController,
    Helper $wooHelper,
    WPFunctions $wp
  ) {
    $this->featuresController = $featuresController;
    $this->wooHelper = $wooHelper;
    $this->wp = $wp;
  }

  /**
   * @param int[] $newsletterIds
   * @return array<int, array{total: float, count: int}>|null
   */
  public function getNewsletterRevenues(
    array $newsletterIds,
    ?\DateTimeImmutable $from = null,
    ?\DateTimeImmutable $to = null
  ): ?array {
    $boundary = $this->getReadBoundary();
    if ($boundary === null) {
      return null;
    }

    $newsletterIds = $this->normalizeIds($newsletterIds);
    if (!$newsletterIds) {
      return [];
    }

    $currency = $this->wooHelper->getWoocommerceCurrency();
    $purchaseStates = $this->wooHelper->getPurchaseStates();
    $revenues = [];
    $beforeBoundaryTo = $this->getBeforeBoundaryTo($to, $boundary);
    $afterBoundaryFrom = $this->getAfterBoundaryFrom($from, $boundary);

    $this->mergeNewsletterRows(
      $revenues,
      $this->getLegacyNewsletterRevenues($newsletterIds, $currency, $purchaseStates, $from, $beforeBoundaryTo, false, $this->isBoundaryUpperLimit($to, $boundary))
    );
    $this->mergeNewsletterRows(
      $revenues,
      $this->getWooNewsletterRevenues($newsletterIds, $currency, $purchaseStates, $afterBoundaryFrom, $to)
    );
    $this->mergeNewsletterRows(
      $revenues,
      $this->getLegacyNewsletterRevenues($newsletterIds, $currency, $purchaseStates, $afterBoundaryFrom, $to, true, false)
    );

    return $revenues;
  }

  /**
   * @return array{total: float, count: int}|null
   */
  public function getSubscriberRevenue(int $subscriberId, ?\DateTimeInterface $startTime = null): ?array {
    $boundary = $this->getReadBoundary();
    if ($boundary === null) {
      return null;
    }

    $currency = $this->wooHelper->getWoocommerceCurrency();
    $purchaseStates = $this->wooHelper->getPurchaseStates();
    $revenue = ['total' => 0.0, 'count' => 0];
    $beforeBoundaryTo = $this->getBeforeBoundaryTo(null, $boundary);
    $afterBoundaryFrom = $this->getAfterBoundaryFrom($startTime, $boundary);

    $this->mergeRevenue(
      $revenue,
      $this->getLegacySubscriberRevenue($subscriberId, $currency, $purchaseStates, $startTime, $beforeBoundaryTo, false, true)
    );
    $this->mergeRevenue(
      $revenue,
      $this->getWooSubscriberRevenue($subscriberId, $currency, $purchaseStates, $afterBoundaryFrom, null)
    );
    $this->mergeRevenue(
      $revenue,
      $this->getLegacySubscriberRevenue($subscriberId, $currency, $purchaseStates, $afterBoundaryFrom, null, true, false)
    );

    return $revenue;
  }

  /**
   * @param int[] $newsletterIds
   * @return OrderRow[]|null
   */
  public function getNewsletterOrderRows(
    array $newsletterIds,
    \DateTimeImmutable $from,
    \DateTimeImmutable $to
  ): ?array {
    $boundary = $this->getReadBoundary();
    if ($boundary === null) {
      return null;
    }

    $newsletterIds = $this->normalizeIds($newsletterIds);
    if (!$newsletterIds) {
      return [];
    }

    $beforeBoundaryTo = $this->getBeforeBoundaryTo($to, $boundary);
    $afterBoundaryFrom = $this->getAfterBoundaryFrom($from, $boundary);

    return array_merge(
      $this->getLegacyNewsletterOrderRows($newsletterIds, $from, $beforeBoundaryTo, false, $this->isBoundaryUpperLimit($to, $boundary)),
      $this->getWooNewsletterOrderRows($newsletterIds, $afterBoundaryFrom, $to),
      $this->getLegacyNewsletterOrderRows($newsletterIds, $afterBoundaryFrom, $to, true, false)
    );
  }

  private function getReadBoundary(): ?\DateTimeImmutable {
    if (!$this->wooHelper->isWooCommerceActive()) {
      return null;
    }
    if (!$this->featuresController->isSupported(FeaturesController::FEATURE_WOO_BACKED_REVENUE_REPORTING)) {
      return null;
    }

    $boundary = $this->wp->getOption(OrderAttributionWriter::WRITES_STARTED_AT_OPTION);
    if (!is_string($boundary) || $boundary === '') {
      return null;
    }

    try {
      return new \DateTimeImmutable($boundary, new \DateTimeZone('UTC'));
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * @param int[] $ids
   * @return int[]
   */
  private function normalizeIds(array $ids): array {
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function(int $id): bool {
      return $id > 0;
    });
    return array_values(array_unique($ids));
  }

  private function getBeforeBoundaryTo(?\DateTimeInterface $to, \DateTimeImmutable $boundary): \DateTimeInterface {
    if ($to !== null && $to->getTimestamp() < $boundary->getTimestamp()) {
      return $to;
    }
    return $boundary;
  }

  private function getAfterBoundaryFrom(?\DateTimeInterface $from, \DateTimeImmutable $boundary): \DateTimeInterface {
    if ($from !== null && $from->getTimestamp() > $boundary->getTimestamp()) {
      return $from;
    }
    return $boundary;
  }

  private function isBoundaryUpperLimit(?\DateTimeInterface $to, \DateTimeImmutable $boundary): bool {
    return $to === null || $to->getTimestamp() >= $boundary->getTimestamp();
  }

  /**
   * @param int[] $newsletterIds
   * @param string[] $purchaseStates
   * @return array<int, array{total: float, count: int}>
   */
  private function getLegacyNewsletterRevenues(
    array $newsletterIds,
    string $currency,
    array $purchaseStates,
    ?\DateTimeInterface $from,
    ?\DateTimeInterface $to,
    bool $excludeResolvedSource,
    bool $excludeTo
  ): array {
    if (!$purchaseStates || $this->isEmptyDateRange($from, $to, $excludeTo)) {
      return [];
    }

    global $wpdb;

    $dateParams = [];
    $dateSql = $this->getDateRangeSql('swp.created_at', $from, $to, true, $excludeTo, $dateParams);
    $excludeParams = [];
    $excludeSql = $excludeResolvedSource ? $this->getResolvedSourceExclusionSql('swp.order_id', $excludeParams) : '';
    $newsletterPlaceholders = implode(',', array_fill(0, count($newsletterIds), '%d'));
    $statePlaceholders = implode(',', array_fill(0, count($purchaseStates), '%s'));

    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic fragments are trusted identifiers/placeholders; values are prepared below.
    $query = $wpdb->prepare(
      '
        SELECT swp.newsletter_id AS newsletter_id, SUM(swp.order_price_total) AS total, COUNT(swp.id) AS count
        FROM %i swp
        LEFT JOIN %i q ON q.id = swp.queue_id
        WHERE swp.newsletter_id IN (' . $newsletterPlaceholders . ')
          AND swp.order_currency = %s
          AND swp.status IN (' . $statePlaceholders . ')
          AND (q.id IS NULL OR q.meta IS NULL OR q.meta NOT LIKE %s)
          ' . $dateSql . '
          ' . $excludeSql . '
        GROUP BY swp.newsletter_id
      ',
      array_merge(
        [
          $wpdb->prefix . 'mailpoet_statistics_woocommerce_purchases',
          $wpdb->prefix . 'mailpoet_sending_queues',
        ],
        $newsletterIds,
        [$currency],
        $purchaseStates,
        [NewsletterReplayMetadata::getMetaLikePattern()],
        $dateParams,
        $excludeParams
      )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

    $rows = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $result = [];
    foreach ($rows ?: [] as $row) {
      $result[(int)$row['newsletter_id']] = [
        'total' => (float)$row['total'],
        'count' => (int)$row['count'],
      ];
    }
    return $result;
  }

  /**
   * @param int[] $newsletterIds
   * @param string[] $purchaseStates
   * @return array<int, array{total: float, count: int}>
   */
  private function getWooNewsletterRevenues(
    array $newsletterIds,
    string $currency,
    array $purchaseStates,
    \DateTimeInterface $from,
    ?\DateTimeInterface $to
  ): array {
    if ($this->isEmptyDateRange($from, $to, false)) {
      return [];
    }

    $rows = $this->excludeReplayAttributionRows($this->getWooAttributedOrders($from, $to, $newsletterIds, null));
    $result = [];
    foreach ($rows as $row) {
      $order = $this->wooHelper->wcGetOrder((int)$row['order_id']);
      if (!$this->isRevenueOrder($order, $currency, $purchaseStates)) {
        continue;
      }
      $newsletterId = (int)$row['newsletter_id'];
      $this->addNewsletterRevenue($result, $newsletterId, (float)$order->get_remaining_refund_amount(), 1);
    }
    return $result;
  }

  /**
   * @param string[] $purchaseStates
   * @return array{total: float, count: int}
   */
  private function getLegacySubscriberRevenue(
    int $subscriberId,
    string $currency,
    array $purchaseStates,
    ?\DateTimeInterface $from,
    ?\DateTimeInterface $to,
    bool $excludeResolvedSource,
    bool $excludeTo
  ): array {
    if (!$purchaseStates || $this->isEmptyDateRange($from, $to, $excludeTo)) {
      return ['total' => 0.0, 'count' => 0];
    }

    global $wpdb;

    $dateParams = [];
    $dateSql = $this->getDateRangeSql('swp.created_at', $from, $to, true, $excludeTo, $dateParams);
    $excludeParams = [];
    $excludeSql = $excludeResolvedSource ? $this->getResolvedSourceExclusionSql('swp.order_id', $excludeParams) : '';
    $statePlaceholders = implode(',', array_fill(0, count($purchaseStates), '%s'));

    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic fragments are trusted identifiers/placeholders; values are prepared below.
    $query = $wpdb->prepare(
      '
        SELECT swp.order_id, swp.order_price_total
        FROM %i swp
        WHERE swp.subscriber_id = %d
          AND swp.order_currency = %s
          AND swp.status IN (' . $statePlaceholders . ')
          ' . $dateSql . '
          ' . $excludeSql . '
        GROUP BY swp.order_id, swp.order_price_total
      ',
      array_merge(
        [
          $wpdb->prefix . 'mailpoet_statistics_woocommerce_purchases',
          $subscriberId,
          $currency,
        ],
        $purchaseStates,
        $dateParams,
        $excludeParams
      )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

    $rows = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $revenue = ['total' => 0.0, 'count' => 0];
    foreach ($rows ?: [] as $row) {
      $this->mergeRevenue($revenue, [
        'total' => (float)$row['order_price_total'],
        'count' => 1,
      ]);
    }
    return $revenue;
  }

  /**
   * @param string[] $purchaseStates
   * @return array{total: float, count: int}
   */
  private function getWooSubscriberRevenue(
    int $subscriberId,
    string $currency,
    array $purchaseStates,
    \DateTimeInterface $from,
    ?\DateTimeInterface $to
  ): array {
    if ($this->isEmptyDateRange($from, $to, false)) {
      return ['total' => 0.0, 'count' => 0];
    }

    $rows = $this->getWooAttributedOrders($from, $to, [], $subscriberId);
    $revenue = ['total' => 0.0, 'count' => 0];
    foreach ($rows as $row) {
      $order = $this->wooHelper->wcGetOrder((int)$row['order_id']);
      if (!$this->isRevenueOrder($order, $currency, $purchaseStates)) {
        continue;
      }
      $this->mergeRevenue($revenue, [
        'total' => (float)$order->get_remaining_refund_amount(),
        'count' => 1,
      ]);
    }
    return $revenue;
  }

  /**
   * @param int[] $newsletterIds
   * @return OrderRow[]
   */
  private function getLegacyNewsletterOrderRows(
    array $newsletterIds,
    ?\DateTimeInterface $from,
    ?\DateTimeInterface $to,
    bool $excludeResolvedSource,
    bool $excludeTo
  ): array {
    if ($this->isEmptyDateRange($from, $to, $excludeTo)) {
      return [];
    }

    global $wpdb;

    $dateParams = [];
    $dateSql = $this->getDateRangeSql('swp.created_at', $from, $to, true, $excludeTo, $dateParams);
    $excludeParams = [];
    $excludeSql = $excludeResolvedSource ? $this->getResolvedSourceExclusionSql('swp.order_id', $excludeParams) : '';
    $newsletterPlaceholders = implode(',', array_fill(0, count($newsletterIds), '%d'));
    $orderTable = $this->getOrderTable();
    $orderStatusColumn = $this->wooHelper->isWooCommerceCustomOrdersTableEnabled()
      ? '`woo_order`.`status`'
      : '`woo_order`.`post_status`';
    $orderIdColumn = $this->wooHelper->isWooCommerceCustomOrdersTableEnabled()
      ? '`woo_order`.`id`'
      : '`woo_order`.`ID`';

    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic fragments are trusted identifiers/placeholders; values are prepared below.
    $query = $wpdb->prepare(
      '
        SELECT
          swp.created_at,
          swp.newsletter_id,
          swp.order_id,
          swp.order_price_total AS total,
          swp.subscriber_id,
          subscriber.first_name,
          subscriber.last_name,
          subscriber.email,
          newsletter.subject,
          ' . $orderStatusColumn . ' AS status
        FROM %i swp
        INNER JOIN %i woo_order ON swp.order_id = ' . $orderIdColumn . '
        INNER JOIN %i subscriber ON subscriber.ID = swp.subscriber_id
        INNER JOIN %i newsletter ON newsletter.ID = swp.newsletter_id
        LEFT JOIN %i q ON q.id = swp.queue_id
        WHERE swp.newsletter_id IN (' . $newsletterPlaceholders . ')
          AND (q.id IS NULL OR q.meta IS NULL OR q.meta NOT LIKE %s)
          ' . $dateSql . '
          ' . $excludeSql . '
      ',
      array_merge(
        [
          $wpdb->prefix . 'mailpoet_statistics_woocommerce_purchases',
          $orderTable,
          $wpdb->prefix . 'mailpoet_subscribers',
          $wpdb->prefix . 'mailpoet_newsletters',
          $wpdb->prefix . 'mailpoet_sending_queues',
        ],
        $newsletterIds,
        [NewsletterReplayMetadata::getMetaLikePattern()],
        $dateParams,
        $excludeParams
      )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

    $rows = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    return $this->normalizeOrderRows(is_array($rows) ? $rows : []);
  }

  /**
   * @param int[] $newsletterIds
   * @return OrderRow[]
   */
  private function getWooNewsletterOrderRows(
    array $newsletterIds,
    \DateTimeInterface $from,
    ?\DateTimeInterface $to
  ): array {
    if ($this->isEmptyDateRange($from, $to, false)) {
      return [];
    }

    $attributionRows = $this->excludeReplayAttributionRows($this->getWooAttributedOrders($from, $to, $newsletterIds, null));
    if (!$attributionRows) {
      return [];
    }

    $subscriberIds = $this->normalizeIds(array_map(function(array $row): int {
      return (int)($row['subscriber_id'] ?? 0);
    }, $attributionRows));
    $newsletterIds = $this->normalizeIds(array_map(function(array $row): int {
      return (int)$row['newsletter_id'];
    }, $attributionRows));
    $subscribers = $this->getSubscribersByIds($subscriberIds);
    $newsletters = $this->getNewslettersByIds($newsletterIds);

    $rows = [];
    foreach ($attributionRows as $row) {
      $subscriberId = (int)($row['subscriber_id'] ?? 0);
      $newsletterId = (int)$row['newsletter_id'];
      if (!isset($subscribers[$subscriberId], $newsletters[$newsletterId])) {
        continue;
      }

      $order = $this->wooHelper->wcGetOrder((int)$row['order_id']);
      if (!$order instanceof WC_Order) {
        continue;
      }

      $subscriber = $subscribers[$subscriberId];
      $newsletter = $newsletters[$newsletterId];
      $rows[] = [
        'created_at' => (string)$row['date_created_gmt'],
        'newsletter_id' => $newsletterId,
        'order_id' => (int)$row['order_id'],
        'total' => (float)$order->get_remaining_refund_amount(),
        'subscriber_id' => $subscriberId,
        'first_name' => $subscriber['first_name'],
        'last_name' => $subscriber['last_name'],
        'email' => $subscriber['email'],
        'subject' => $newsletter['subject'],
        'status' => 'wc-' . $order->get_status(),
      ];
    }
    return $rows;
  }

  /**
   * @param int[] $newsletterIds
   * @return AttributionRow[]
   */
  private function getWooAttributedOrders(
    \DateTimeInterface $from,
    ?\DateTimeInterface $to,
    array $newsletterIds,
    ?int $subscriberId
  ): array {
    global $wpdb;

    $orderLookup = $this->getOrderLookupTable();
    $orderIdColumn = 'woo_order.' . $orderLookup['id_column'];
    $orderDateColumn = 'woo_order.' . $orderLookup['date_column'];
    $dateParams = [];
    $dateSql = $this->getDateRangeSql($orderDateColumn, $from, $to, true, false, $dateParams);
    $typeSql = $orderLookup['type_column'] !== null ? ' AND woo_order.' . $orderLookup['type_column'] . ' = %s' : '';
    $typeParams = $orderLookup['type_column'] !== null ? ['shop_order'] : [];
    $meta = $this->getOrderMetaTable();
    $utmSourceMetaKey = OrderAttributionFields::getMetaKey('utm_source');
    $purchasesTable = $wpdb->prefix . 'mailpoet_statistics_woocommerce_purchases';
    $clicksTable = $wpdb->prefix . 'mailpoet_statistics_clicks';

    $filterSql = '';
    $filterParams = [];
    if ($newsletterIds) {
      $filterSql .= ' AND p.newsletter_id IN (' . implode(',', array_fill(0, count($newsletterIds), '%d')) . ')';
      $filterParams = array_merge($filterParams, array_map('intval', $newsletterIds));
    }
    if ($subscriberId !== null) {
      $filterSql .= ' AND p.subscriber_id = %d';
      $filterParams[] = $subscriberId;
    }

    // The MailPoet namespace meta that pre-resolved the canonical click was dropped
    // (STOMAIL-8200). Per-order newsletter/subscriber/queue/click detail is recovered
    // from the legacy statistics_woocommerce_purchases rows instead, gated on the
    // order's standard source resolving to mailpoet (the won-arbitration marker). The
    // self-anti-join keeps the single most recent click per order, matching
    // OrderAttributionWriter::isMoreRecent (updated_at desc, then click id desc).
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic fragments are trusted identifiers/placeholders; values are prepared below.
    $query = $wpdb->prepare(
      '
        SELECT
          ' . $orderIdColumn . ' AS order_id,
          ' . $orderDateColumn . ' AS date_created_gmt,
          p.newsletter_id AS newsletter_id,
          p.subscriber_id AS subscriber_id,
          p.queue_id AS queue_id
        FROM %i woo_order
        INNER JOIN %i source_meta ON source_meta.%i = ' . $orderIdColumn . '
          AND source_meta.meta_key = %s
          AND source_meta.meta_value = %s
        INNER JOIN %i p ON p.order_id = ' . $orderIdColumn . '
        INNER JOIN %i c ON c.id = p.click_id
        LEFT JOIN (%i p2 INNER JOIN %i c2 ON c2.id = p2.click_id) ON p2.order_id = p.order_id
          AND (c2.updated_at > c.updated_at OR (c2.updated_at = c.updated_at AND p2.click_id > p.click_id))
        WHERE p2.id IS NULL
          ' . $typeSql . '
          ' . $dateSql . '
          ' . $filterSql . '
      ',
      array_merge(
        [
          $orderLookup['table'],
          $meta['table'],
          $meta['order_id_column'],
          $utmSourceMetaKey,
          'mailpoet',
          $purchasesTable,
          $clicksTable,
          $purchasesTable,
          $clicksTable,
        ],
        $typeParams,
        $dateParams,
        $filterParams
      )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

    $rows = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    return $this->normalizeAttributionRows(is_array($rows) ? $rows : []);
  }

  /**
   * @param AttributionRow[] $rows
   * @return AttributionRow[]
   */
  private function excludeReplayAttributionRows(array $rows): array {
    $queueIds = $this->normalizeIds(array_map(function(array $row): int {
      return (int)($row['queue_id'] ?? 0);
    }, $rows));
    if (!$queueIds) {
      return $rows;
    }

    global $wpdb;

    $placeholders = implode(',', array_fill(0, count($queueIds), '%d'));
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic fragments are trusted placeholders; values are prepared below.
    $query = $wpdb->prepare(
      '
        SELECT id
        FROM %i
        WHERE id IN (' . $placeholders . ')
          AND meta LIKE %s
      ',
      array_merge(
        [$wpdb->prefix . 'mailpoet_sending_queues'],
        $queueIds,
        [NewsletterReplayMetadata::getMetaLikePattern()]
      )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

    $replayQueueIds = array_map('intval', (array)$wpdb->get_col($query)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    if (!$replayQueueIds) {
      return $rows;
    }

    $replayQueueIds = array_flip($replayQueueIds);
    return array_values(array_filter($rows, function(array $row) use ($replayQueueIds): bool {
      $queueId = (int)($row['queue_id'] ?? 0);
      return $queueId <= 0 || !isset($replayQueueIds[$queueId]);
    }));
  }

  /**
   * @return array{table: string, order_id_column: string}
   */
  private function getOrderMetaTable(): array {
    global $wpdb;

    if ($this->wooHelper->isWooCommerceCustomOrdersTableEnabled()) {
      return [
        'table' => $wpdb->prefix . 'wc_orders_meta',
        'order_id_column' => 'order_id',
      ];
    }

    return [
      'table' => $wpdb->postmeta,
      'order_id_column' => 'post_id',
    ];
  }

  private function getOrderTable(): string {
    global $wpdb;

    return $this->wooHelper->isWooCommerceCustomOrdersTableEnabled()
      ? $wpdb->prefix . 'wc_orders'
      : $wpdb->posts;
  }

  /**
   * @return array{table: string, id_column: string, date_column: string, type_column: string|null}
   */
  private function getOrderLookupTable(): array {
    global $wpdb;

    if ($this->wooHelper->isWooCommerceCustomOrdersTableEnabled()) {
      return [
        'table' => $wpdb->prefix . 'wc_orders',
        'id_column' => 'id',
        'date_column' => 'date_created_gmt',
        'type_column' => 'type',
      ];
    }

    return [
      'table' => $wpdb->posts,
      'id_column' => 'ID',
      'date_column' => 'post_date_gmt',
      'type_column' => 'post_type',
    ];
  }

  private function getDateRangeSql(
    string $column,
    ?\DateTimeInterface $from,
    ?\DateTimeInterface $to,
    bool $includeFrom,
    bool $excludeTo,
    array &$params
  ): string {
    $params = [];
    $sql = '';
    if ($from !== null) {
      $sql .= ' AND ' . $column . ($includeFrom ? ' >= %s' : ' > %s');
      $params[] = $this->formatDate($from);
    }
    if ($to !== null) {
      $sql .= ' AND ' . $column . ($excludeTo ? ' < %s' : ' <= %s');
      $params[] = $this->formatDate($to);
    }
    return $sql;
  }

  private function isEmptyDateRange(?\DateTimeInterface $from, ?\DateTimeInterface $to, bool $excludeTo): bool {
    if ($from === null || $to === null) {
      return false;
    }
    if ($excludeTo) {
      return $from->getTimestamp() >= $to->getTimestamp();
    }
    return $from->getTimestamp() > $to->getTimestamp();
  }

  /**
   * Post-boundary legacy fallback exclusion. Skips orders whose Woo standard
   * attribution source is already resolved (any non-empty utm_source): those are
   * either counted by the Woo path (source = mailpoet) or intentionally left out
   * for parity (a non-MailPoet source won last-click). Orders with no resolved
   * source — e.g. Woo attribution unavailable when the order was tracked — fall
   * back to the legacy table so their revenue is not dropped.
   *
   * @param array<int, string> $params
   */
  private function getResolvedSourceExclusionSql(string $orderIdExpression, array &$params): string {
    $meta = $this->getOrderMetaTable();
    $params = [
      $meta['table'],
      $meta['order_id_column'],
      OrderAttributionFields::getMetaKey('utm_source'),
    ];
    return ' AND NOT EXISTS (SELECT 1 FROM %i woo_meta WHERE woo_meta.%i = ' . $orderIdExpression . ' AND woo_meta.meta_key = %s AND woo_meta.meta_value <> \'\')';
  }

  /**
   * @param int[] $subscriberIds
   * @return array<int, array{first_name:string, last_name:string, email:string}>
   */
  private function getSubscribersByIds(array $subscriberIds): array {
    if (!$subscriberIds) {
      return [];
    }

    global $wpdb;

    $placeholders = implode(',', array_fill(0, count($subscriberIds), '%d'));
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Dynamic fragments are trusted placeholders; values are prepared below.
    $query = $wpdb->prepare(
      '
        SELECT ID, first_name, last_name, email
        FROM %i
        WHERE ID IN (' . $placeholders . ')
      ',
      array_merge(
        [$wpdb->prefix . 'mailpoet_subscribers'],
        $subscriberIds
      )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

    $rows = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $subscribers = [];
    foreach ($rows ?: [] as $row) {
      $subscribers[(int)$row['ID']] = [
        'first_name' => (string)$row['first_name'],
        'last_name' => (string)$row['last_name'],
        'email' => (string)$row['email'],
      ];
    }
    return $subscribers;
  }

  /**
   * @param int[] $newsletterIds
   * @return array<int, array{subject:string}>
   */
  private function getNewslettersByIds(array $newsletterIds): array {
    if (!$newsletterIds) {
      return [];
    }

    global $wpdb;

    $placeholders = implode(',', array_fill(0, count($newsletterIds), '%d'));
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Dynamic fragments are trusted placeholders; values are prepared below.
    $query = $wpdb->prepare(
      '
        SELECT ID, subject
        FROM %i
        WHERE ID IN (' . $placeholders . ')
      ',
      array_merge(
        [$wpdb->prefix . 'mailpoet_newsletters'],
        $newsletterIds
      )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

    $rows = $wpdb->get_results($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
    $newsletters = [];
    foreach ($rows ?: [] as $row) {
      $newsletters[(int)$row['ID']] = [
        'subject' => (string)$row['subject'],
      ];
    }
    return $newsletters;
  }

  /**
   * @param array<int, mixed> $rows
   * @return OrderRow[]
   */
  private function normalizeOrderRows(array $rows): array {
    $result = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $normalized = $this->normalizeOrderRow($row);
      if ($normalized !== null) {
        $result[] = $normalized;
      }
    }
    return $result;
  }

  /**
   * @param array<mixed, mixed> $row
   * @return OrderRow|null
   */
  private function normalizeOrderRow(array $row): ?array {
    $createdAt = $this->toString($row['created_at'] ?? null);
    $newsletterId = $this->toInt($row['newsletter_id'] ?? null);
    $orderId = $this->toInt($row['order_id'] ?? null);
    $total = $this->toFloat($row['total'] ?? null);
    $subscriberId = $this->toInt($row['subscriber_id'] ?? null);
    $firstName = $this->toString($row['first_name'] ?? null);
    $lastName = $this->toString($row['last_name'] ?? null);
    $email = $this->toString($row['email'] ?? null);
    $subject = $this->toString($row['subject'] ?? null);
    $status = $this->toString($row['status'] ?? null);

    if (
      $createdAt === null
      || $newsletterId === null
      || $orderId === null
      || $total === null
      || $subscriberId === null
      || $firstName === null
      || $lastName === null
      || $email === null
      || $subject === null
      || $status === null
    ) {
      return null;
    }

    return [
      'created_at' => $createdAt,
      'newsletter_id' => $newsletterId,
      'order_id' => $orderId,
      'total' => $total,
      'subscriber_id' => $subscriberId,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email' => $email,
      'subject' => $subject,
      'status' => $status,
    ];
  }

  /**
   * @param array<int, mixed> $rows
   * @return AttributionRow[]
   */
  private function normalizeAttributionRows(array $rows): array {
    $result = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $orderId = $this->toInt($row['order_id'] ?? null);
      $dateCreated = $this->toString($row['date_created_gmt'] ?? null);
      $newsletterId = $this->toString($row['newsletter_id'] ?? null);
      $subscriberId = $this->toString($row['subscriber_id'] ?? null);
      $queueId = $this->toString($row['queue_id'] ?? null);
      if ($orderId === null || $dateCreated === null || $newsletterId === null) {
        continue;
      }
      $result[] = [
        'order_id' => $orderId,
        'date_created_gmt' => $dateCreated,
        'newsletter_id' => $newsletterId,
        'subscriber_id' => $subscriberId,
        'queue_id' => $queueId,
      ];
    }
    return $result;
  }

  private function toString($value): ?string {
    return is_scalar($value) ? (string)$value : null;
  }

  private function toInt($value): ?int {
    return is_numeric($value) ? (int)$value : null;
  }

  private function toFloat($value): ?float {
    return is_numeric($value) ? (float)$value : null;
  }

  private function isRevenueOrder($order, string $currency, array $purchaseStates): bool {
    return $order instanceof WC_Order
      && $order->get_currency() === $currency
      && in_array($order->get_status(), $purchaseStates, true);
  }

  private function formatDate(\DateTimeInterface $date): string {
    return gmdate('Y-m-d H:i:s', $date->getTimestamp());
  }

  /**
   * @param array<int, array{total: float, count: int}> $target
   * @param array<int, array{total: float, count: int}> $rows
   */
  private function mergeNewsletterRows(array &$target, array $rows): void {
    foreach ($rows as $newsletterId => $row) {
      $this->addNewsletterRevenue($target, (int)$newsletterId, $row['total'], $row['count']);
    }
  }

  /**
   * @param array<int, array{total: float, count: int}> $target
   */
  private function addNewsletterRevenue(array &$target, int $newsletterId, float $total, int $count): void {
    if (!isset($target[$newsletterId])) {
      $target[$newsletterId] = ['total' => 0.0, 'count' => 0];
    }
    $target[$newsletterId]['total'] += $total;
    $target[$newsletterId]['count'] += $count;
  }

  /**
   * @param array{total: float, count: int} $target
   * @param array{total: float, count: int} $source
   */
  private function mergeRevenue(array &$target, array $source): void {
    $target['total'] += $source['total'];
    $target['count'] += $source['count'];
  }
}
