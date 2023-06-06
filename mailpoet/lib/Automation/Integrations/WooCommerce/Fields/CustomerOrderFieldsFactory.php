<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use DateTimeZone;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use WC_Customer;

class CustomerOrderFieldsFactory {
  /** @var WooCommerce */
  private $wooCommerce;

  /** @var WordPress */
  private $wordPress;

  /** @var TermOptionsBuilder */
  private $termOptionsBuilder;

  public function __construct(
    WordPress $wordPress,
    WooCommerce $wooCommerce,
    TermOptionsBuilder $termOptionsBuilder
  ) {
    $this->wordPress = $wordPress;
    $this->wooCommerce = $wooCommerce;
    $this->termOptionsBuilder = $termOptionsBuilder;
  }

  /** @return Field[] */
  public function getFields(): array {
    return [
      new Field(
        'woocommerce:customer:spent-total',
        Field::TYPE_NUMBER,
        __('Total spent', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? (float)$customer->get_total_spent() : 0.0;
        }
      ),
      new Field(
        'woocommerce:customer:spent-average',
        Field::TYPE_NUMBER,
        __('Average spent', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          $totalSpent = $customer ? (float)$customer->get_total_spent() : 0.0;
          $orderCount = $customer ? (int)$customer->get_order_count() : 0;
          return $orderCount > 0 ? ($totalSpent / $orderCount) : 0.0;
        }
      ),
      new Field(
        'woocommerce:customer:order-count',
        Field::TYPE_INTEGER,
        __('Order count', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_order_count() : 0;
        }
      ),
      new Field(
        'woocommerce:customer:first-paid-order-date',
        Field::TYPE_DATETIME,
        __('First paid order date', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          if (!$customer) {
            return null;
          }
          return $this->getPaidOrderDate($customer, true);
        }
      ),
      new Field(
        'woocommerce:customer:last-paid-order-date',
        Field::TYPE_DATETIME,
        __('Last paid order date', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          if (!$customer) {
            return null;
          }
          return $this->getPaidOrderDate($customer, false);
        }
      ),
      new Field(
        'woocommerce:customer:purchased-categories',
        Field::TYPE_ENUM_ARRAY,
        __('Purchased categories', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $this->getOrderProductTermIds($customer, 'product_cat') : [];
        },
        [
          'options' => $this->termOptionsBuilder->getCategoryOptions(),
        ]
      ),
      new Field(
        'woocommerce:customer:purchased-tags',
        Field::TYPE_ENUM_ARRAY,
        __('Purchased tags', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $this->getOrderProductTermIds($customer, 'product_tag') : [];
        },
        [
          'options' => $this->termOptionsBuilder->getTagOptions(),
        ]
      ),
    ];
  }

  private function getPaidOrderDate(WC_Customer $customer, bool $fetchFirst): ?DateTimeImmutable {
    $wpdb = $this->wordPress->getWpdb();
    $sorting = $fetchFirst ? 'ASC' : 'DESC';
    $statuses = array_map(function (string $status) {
      return "wc-$status";
    }, $this->wooCommerce->wcGetIsPaidStatuses());
    $statusesPlaceholder = implode(',', array_fill(0, count($statuses), '%s'));

    if ($this->wooCommerce->isWooCommerceCustomOrdersTableEnabled()) {
      $statement = (string)$wpdb->prepare("
        SELECT o.date_created_gmt
        FROM {$wpdb->prefix}wc_orders o
        WHERE o.customer_id = %d
        AND o.status IN ($statusesPlaceholder)
        AND o.total_amount > 0
        ORDER BY o.date_created_gmt {$sorting}
        LIMIT 1
      ", array_merge([$customer->get_id()], $statuses));
    } else {
      $statement = (string)$wpdb->prepare("
        SELECT p.post_date_gmt
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
        LEFT JOIN {$wpdb->prefix}postmeta pm_user ON p.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ($statusesPlaceholder)
        AND pm_user.meta_value = %d
        AND pm_total.meta_value > 0
        ORDER BY p.post_date_gmt {$sorting}
        LIMIT 1
      ", array_merge($statuses, [$customer->get_id()]));
    }

    $date = $wpdb->get_var($statement);
    return $date ? new DateTimeImmutable($date, new DateTimeZone('GMT')) : null;
  }

  private function getOrderProductTermIds(WC_Customer $customer, string $taxonomy): array {
    $wpdb = $this->wordPress->getWpdb();

    $statuses = array_map(function (string $status) {
      return "wc-$status";
    }, $this->wooCommerce->wcGetIsPaidStatuses());
    $statusesPlaceholder = implode(',', array_fill(0, count($statuses), '%s'));

    // get all product categories that the customer has purchased
    if ($this->wooCommerce->isWooCommerceCustomOrdersTableEnabled()) {
      $orderIdsSubquery = "
        SELECT o.id
        FROM {$wpdb->prefix}wc_orders o
        WHERE o.status IN ($statusesPlaceholder)
        AND o.customer_id = %d
      ";
    } else {
      $orderIdsSubquery = "
        SELECT p.ID
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta pm_user ON p.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ($statusesPlaceholder)
        AND pm_user.meta_value = %d
      ";
    }

    $statement = (string)$wpdb->prepare("
      SELECT DISTINCT tt.term_id
      FROM {$wpdb->prefix}term_taxonomy tt
      JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON oi.order_id IN ($orderIdsSubquery) AND oi.order_item_type = 'line_item'
      JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS pid ON oi.order_item_id = pid.order_item_id AND pid.meta_key = '_product_id'
      JOIN {$wpdb->prefix}posts p ON pid.meta_value = p.ID
      JOIN {$wpdb->prefix}term_relationships tr ON IF(p.post_type = 'product_variation', p.post_parent, p.ID) = tr.object_id AND tr.term_taxonomy_id = tt.term_taxonomy_id
      WHERE tt.taxonomy = %s
      ORDER BY tt.term_id ASC
    ", array_merge($statuses, [$customer->get_id(), $taxonomy]));

    return array_map('intval', $wpdb->get_col($statement));
  }
}
