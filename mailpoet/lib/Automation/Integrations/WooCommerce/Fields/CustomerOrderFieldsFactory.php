<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use DateTimeZone;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use WC_Customer;

class CustomerOrderFieldsFactory {
  /** @var WooCommerce */
  private $wooCommerce;

  public function __construct(
    WooCommerce $wooCommerce
  ) {
    $this->wooCommerce = $wooCommerce;
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
}
