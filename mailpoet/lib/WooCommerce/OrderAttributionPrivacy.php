<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use WC_Order;

/**
 * WordPress privacy integration for the MailPoet attribution data stored in Woo
 * order meta (STOMAIL-8137). Woo's own exporter, eraser, and order anonymization
 * do not cover _wc_order_attribution_* meta, so without this the MailPoet
 * identifiers would survive as orphaned personal data. subscriber_id and click_id
 * identify a person and are removed; newsletter_id and queue_id are campaign-level
 * and are kept so anonymized orders stay attributable to a campaign.
 */
class OrderAttributionPrivacy {
  const LIMIT = 100;

  const PERSONAL_FIELD_NAMES = [
    OrderAttributionFields::FIELD_CLICK_ID,
    OrderAttributionFields::FIELD_SUBSCRIBER_ID,
  ];

  const SUBSCRIBER_ID_QUERY_VAR = 'mailpoet_attribution_subscriber_id';

  /** @var WPFunctions */
  private $wp;

  /** @var Helper */
  private $wooHelper;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    WPFunctions $wp,
    Helper $wooHelper,
    SubscribersRepository $subscribersRepository
  ) {
    $this->wp = $wp;
    $this->wooHelper = $wooHelper;
    $this->subscribersRepository = $subscribersRepository;
  }

  /**
   * @param mixed $email
   * @param mixed $page
   */
  public function export($email, $page = 1): array {
    $data = [];
    $orders = $this->getAttributedOrders($email, is_numeric($page) ? max(1, (int)$page) : 1);
    foreach ($orders as $order) {
      $data[] = $this->getOrderData($order);
    }
    return [
      'data' => $data,
      'done' => count($orders) < self::LIMIT,
    ];
  }

  /**
   * Always reads the first page: erasing removes the subscriber_id meta the
   * query matches on, so the result set shrinks with every pass and WordPress
   * keeps calling until done.
   *
   * @param mixed $email
   * @param mixed $page
   */
  public function erase($email, $page = 1): array {
    $orders = $this->getAttributedOrders($email, 1);
    foreach ($orders as $order) {
      $this->removeOrderPersonalData($order);
    }
    return [
      'items_removed' => count($orders) > 0,
      'items_retained' => false,
      'messages' => [],
      'done' => count($orders) < self::LIMIT,
    ];
  }

  /**
   * Also hooked to woocommerce_privacy_remove_order_personal_data so the
   * MailPoet identifiers are removed when an order is anonymized.
   *
   * @param mixed $order
   */
  public function removeOrderPersonalData($order): void {
    if (!$order instanceof WC_Order) {
      return;
    }
    foreach (self::PERSONAL_FIELD_NAMES as $fieldName) {
      $order->delete_meta_data(OrderAttributionWriter::META_PREFIX . $fieldName);
    }
    // The reconciliation record (STOMAIL-8136) embeds the same click identifiers.
    $order->delete_meta_data(OrderAttributionReconciler::RECONCILIATION_META_KEY);
    $order->save_meta_data();
  }

  /**
   * @param mixed $email
   * @return WC_Order[]
   */
  private function getAttributedOrders($email, int $page): array {
    if (!is_string($email) || trim($email) === '' || !$this->wooHelper->isWooCommerceActive()) {
      return [];
    }
    $subscriber = $this->subscribersRepository->findOneBy(['email' => trim($email)]);
    if (!$subscriber instanceof SubscriberEntity) {
      return [];
    }
    $args = [
      'limit' => self::LIMIT,
      'paged' => $page,
      'orderby' => 'ID',
      'order' => 'ASC',
    ];
    $subscriberId = (string)$subscriber->getId();
    if ($this->wooHelper->isWooCommerceCustomOrdersTableEnabled()) {
      $args['meta_query'] = [
        [
          'key' => OrderAttributionWriter::META_PREFIX . OrderAttributionFields::FIELD_SUBSCRIBER_ID,
          'value' => $subscriberId,
        ],
      ];
    } else {
      // The legacy posts datastore rejects meta_query with a doing-it-wrong
      // notice; a custom query var translated through this extension point is
      // WooCommerce's documented way to filter by meta there.
      $this->wp->addFilter(
        'woocommerce_order_data_store_cpt_get_orders_query',
        [$this, 'translateSubscriberIdQueryVar'],
        10,
        2
      );
      $args[self::SUBSCRIBER_ID_QUERY_VAR] = $subscriberId;
    }
    $orders = $this->wooHelper->wcGetOrders($args);
    if (!is_array($orders)) {
      return [];
    }
    return array_values(array_filter($orders, function ($order) {
      return $order instanceof WC_Order;
    }));
  }

  /**
   * @param mixed $query
   * @param mixed $queryVars
   * @return mixed
   */
  public function translateSubscriberIdQueryVar($query, $queryVars) {
    if (!is_array($query) || !is_array($queryVars)) {
      return $query;
    }
    $subscriberId = $queryVars[self::SUBSCRIBER_ID_QUERY_VAR] ?? null;
    if (!is_scalar($subscriberId) || (string)$subscriberId === '') {
      return $query;
    }
    $metaQuery = isset($query['meta_query']) && is_array($query['meta_query']) ? $query['meta_query'] : [];
    $metaQuery[] = [
      'key' => OrderAttributionWriter::META_PREFIX . OrderAttributionFields::FIELD_SUBSCRIBER_ID,
      'value' => (string)$subscriberId,
    ];
    $query['meta_query'] = $metaQuery;
    return $query;
  }

  private function getOrderData(WC_Order $order): array {
    $fieldLabels = [
      OrderAttributionFields::FIELD_CLICK_ID => __('Email click ID', 'mailpoet'),
      OrderAttributionFields::FIELD_NEWSLETTER_ID => __('Email ID', 'mailpoet'),
      OrderAttributionFields::FIELD_QUEUE_ID => __('Sending queue ID', 'mailpoet'),
      OrderAttributionFields::FIELD_SUBSCRIBER_ID => __('Subscriber ID', 'mailpoet'),
    ];
    $data = [
      [
        'name' => __('Order number', 'mailpoet'),
        'value' => $order->get_order_number(),
      ],
    ];
    foreach ($fieldLabels as $fieldName => $label) {
      $value = $order->get_meta(OrderAttributionWriter::META_PREFIX . $fieldName);
      if (!is_scalar($value) || (string)$value === '') {
        continue;
      }
      $data[] = [
        'name' => $label,
        'value' => (string)$value,
      ];
    }
    return [
      'group_id' => 'mailpoet-woocommerce-order-attribution',
      'group_label' => __('MailPoet WooCommerce Order Attribution', 'mailpoet'),
      'item_id' => 'order-' . $order->get_id(),
      'data' => $data,
    ];
  }
}
