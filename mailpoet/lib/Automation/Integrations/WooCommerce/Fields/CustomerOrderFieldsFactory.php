<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use DateTimeZone;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use WC_Customer;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

class CustomerOrderFieldsFactory {
  /** @var WooCommerce */
  private $wooCommerce;

  /** @var TermOptionsBuilder */
  private $termOptionsBuilder;

  /** @var TermParentsLoader */
  private $termParentsLoader;

  public function __construct(
    WooCommerce $wooCommerce,
    TermOptionsBuilder $termOptionsBuilder,
    TermParentsLoader $termParentsLoader
  ) {
    $this->wooCommerce = $wooCommerce;
    $this->termOptionsBuilder = $termOptionsBuilder;
    $this->termParentsLoader = $termParentsLoader;
  }

  /** @return Field[] */
  public function getFields(): array {
    return [
      new Field(
        'woocommerce:customer:spent-total',
        Field::TYPE_NUMBER,
        __('Total spent', 'mailpoet'),
        function (CustomerPayload $payload, array $params = []) {
          $customer = $payload->getCustomer();
          $inTheLastSeconds = isset($params['in_the_last']) ? (int)$params['in_the_last'] : null;
          if (!$customer) {
            $order = $payload->getOrder();
            return $order && $this->isInTheLastSeconds($order, $inTheLastSeconds) ? $payload->getTotalSpent() : 0.0;
          }
          return $inTheLastSeconds === null
            ? $payload->getTotalSpent()
            : $this->getRecentSpentTotal($customer, $inTheLastSeconds);
        },
        [
          'params' => ['in_the_last'],
        ]
      ),
      new Field(
        'woocommerce:customer:spent-average',
        Field::TYPE_NUMBER,
        __('Average spent', 'mailpoet'),
        function (CustomerPayload $payload, array $params = []) {
          $customer = $payload->getCustomer();
          $inTheLastSeconds = isset($params['in_the_last']) ? (int)$params['in_the_last'] : null;

          if (!$customer) {
            $order = $payload->getOrder();
            return $order && $this->isInTheLastSeconds($order, $inTheLastSeconds) ? $payload->getAverageSpent() : 0.0;
          }

          if ($inTheLastSeconds === null) {
            return $payload->getAverageSpent();
          } else {
            $totalSpent = $this->getRecentSpentTotal($customer, $inTheLastSeconds);
            $orderCount = $this->getRecentOrderCount($customer, $inTheLastSeconds);
            return $orderCount > 0 ? ($totalSpent / $orderCount) : 0.0;
          }
        },
        [
          'params' => ['in_the_last'],
        ]
      ),
      new Field(
        'woocommerce:customer:order-count',
        Field::TYPE_INTEGER,
        __('Order count', 'mailpoet'),
        function (CustomerPayload $payload, array $params = []) {
          $customer = $payload->getCustomer();
          $inTheLastSeconds = isset($params['in_the_last']) ? (int)$params['in_the_last'] : null;
          if (!$customer) {
            $order = $payload->getOrder();
            return $order && $this->isInTheLastSeconds($order, $inTheLastSeconds) ? $payload->getOrderCount() : 0;
          }
          return $inTheLastSeconds === null
            ? $payload->getOrderCount()
            : $this->getRecentOrderCount($customer, $inTheLastSeconds);
        },
        [
          'params' => ['in_the_last'],
        ]
      ),
      new Field(
        'woocommerce:customer:first-paid-order-date',
        Field::TYPE_DATETIME,
        __('First paid order date', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          if (!$customer) {
            $order = $payload->getOrder();
            return $order && $order->is_paid() ? $order->get_date_created() : null;
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
            $order = $payload->getOrder();
            return $order && $order->is_paid() ? $order->get_date_created() : null;
          }
          return $this->getPaidOrderDate($customer, false);
        }
      ),
      new Field(
        'woocommerce:customer:purchased-categories',
        Field::TYPE_ENUM_ARRAY,
        __('Purchased categories', 'mailpoet'),
        function (CustomerPayload $payload, array $params = []) {
          $customer = $payload->getCustomer();
          $inTheLastSeconds = isset($params['in_the_last']) ? (int)$params['in_the_last'] : null;
          if (!$customer) {
            $order = $payload->getOrder();
            $items = $order && $order->is_paid() && $this->isInTheLastSeconds($order, $inTheLastSeconds) ? $order->get_items() : [];
            $ids = [];
            foreach ($items as $item) {
              $product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;
              $ids = array_merge($ids, $product instanceof WC_Product ? $product->get_category_ids() : []);
            }
            $ids = array_unique($ids);
          } else {
            $ids = $this->getOrderProductTermIds($customer, 'product_cat', $inTheLastSeconds);
          }
          $ids = array_merge($ids, $this->termParentsLoader->getParentIds($ids));
          sort($ids);
          return $ids;
        },
        [
          'options' => $this->termOptionsBuilder->getTermOptions('product_cat'),
          'params' => ['in_the_last'],
        ]
      ),
      new Field(
        'woocommerce:customer:purchased-tags',
        Field::TYPE_ENUM_ARRAY,
        __('Purchased tags', 'mailpoet'),
        function (CustomerPayload $payload, array $params = []) {
          $customer = $payload->getCustomer();
          $inTheLastSeconds = isset($params['in_the_last']) ? (int)$params['in_the_last'] : null;
          if (!$customer) {
            $order = $payload->getOrder();
            $items = $order && $order->is_paid() && $this->isInTheLastSeconds($order, $inTheLastSeconds) ? $order->get_items() : [];
            $ids = [];
            foreach ($items as $item) {
              $product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;
              $ids = array_merge($ids, $product instanceof WC_Product ? $product->get_tag_ids() : []);
            }
            $ids = array_unique($ids);
          } else {
            $ids = $this->getOrderProductTermIds($customer, 'product_tag', $inTheLastSeconds);
          }
          sort($ids);
          return $ids;
        },
        [
          'options' => $this->termOptionsBuilder->getTermOptions('product_tag'),
          'params' => ['in_the_last'],
        ]
      ),
    ];
  }

  private function getRecentSpentTotal(WC_Customer $customer, int $inTheLastSeconds): float {
    global $wpdb;
    $statuses = array_map(function (string $status) {
      return "wc-$status";
    }, $this->wooCommerce->wcGetIsPaidStatuses());

    if ($this->wooCommerce->isWooCommerceCustomOrdersTableEnabled()) {
      return (float)$wpdb->get_var(
        $wpdb->prepare(
          '
            SELECT SUM(o.total_amount)
            FROM %i o
            WHERE o.customer_id = %d
            AND o.status IN (' . implode(',', array_fill(0, count($statuses), '%s')) . ')
            AND o.date_created_gmt >= DATE_SUB(current_timestamp, INTERVAL %d SECOND)
          ',
          array_merge(
            [
              $wpdb->prefix . 'wc_orders',
              $customer->get_id(),
            ],
            $statuses,
            [$inTheLastSeconds]
          )
        )
      );
    }

    return (float)$wpdb->get_var(
      $wpdb->prepare(
        "
          SELECT SUM(pm_total.meta_value)
          FROM {$wpdb->posts} p
          LEFT JOIN {$wpdb->postmeta} pm_user ON p.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
          LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
          WHERE p.post_type = 'shop_order'
          AND p.post_status IN (" . implode(',', array_fill(0, count($statuses), '%s')) . ")
          AND pm_user.meta_value = %d
          AND p.post_date_gmt >= DATE_SUB(current_timestamp, INTERVAL %d SECOND)
        ",
        array_merge(
          $statuses,
          [$customer->get_id(), $inTheLastSeconds]
        )
      )
    );
  }

  private function getRecentOrderCount(WC_Customer $customer, int $inTheLastSeconds): int {
    global $wpdb;
    $statuses = array_keys($this->wooCommerce->wcGetOrderStatuses());

    if ($this->wooCommerce->isWooCommerceCustomOrdersTableEnabled()) {
      return (int)$wpdb->get_var(
        $wpdb->prepare(
          '
            SELECT COUNT(o.id)
            FROM %i o
            WHERE o.customer_id = %d
            AND o.status IN (' . implode(',', array_fill(0, count($statuses), '%s')) . ')
            AND o.date_created_gmt >= DATE_SUB(current_timestamp, INTERVAL %d SECOND)
          ',
          array_merge(
            [
              $wpdb->prefix . 'wc_orders',
              $customer->get_id(),
            ],
            $statuses,
            [$inTheLastSeconds]
          )
        )
      );
    }

    return (int)$wpdb->get_var(
      $wpdb->prepare(
        "
          SELECT COUNT(p.ID)
          FROM {$wpdb->posts} p
          LEFT JOIN {$wpdb->postmeta} pm_user ON p.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
          WHERE p.post_type = 'shop_order'
          AND p.post_status IN (" . implode(',', array_fill(0, count($statuses), '%s')) . ")
          AND pm_user.meta_value = %d
          AND p.post_date_gmt >= DATE_SUB(current_timestamp, INTERVAL %d SECOND)
        ",
        array_merge(
          $statuses,
          [
            $customer->get_id(),
            $inTheLastSeconds,
          ]
        )
      )
    );
  }

  private function getPaidOrderDate(WC_Customer $customer, bool $fetchFirst): ?DateTimeImmutable {
    global $wpdb;
    $sorting = $fetchFirst ? 'ASC' : 'DESC';
    $statuses = array_map(function (string $status) {
      return "wc-$status";
    }, $this->wooCommerce->wcGetIsPaidStatuses());

    if ($this->wooCommerce->isWooCommerceCustomOrdersTableEnabled()) {
      $date = $wpdb->get_var(
        $wpdb->prepare(
          '
            SELECT o.date_created_gmt
            FROM %i o
            WHERE o.customer_id = %d
            AND o.status IN (' . implode(',', array_fill(0, count($statuses), '%s')) . ')
            AND o.total_amount > 0
            ORDER BY o.date_created_gmt ' . $sorting /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The argument is safe. */ . '
            LIMIT 1
          ',
          array_merge(
            [
              $wpdb->prefix . 'wc_orders',
              $customer->get_id(),
            ],
            $statuses
          )
        )
      );
    } else {
      $date = $wpdb->get_var(
        $wpdb->prepare(
          "
            SELECT p.post_date_gmt
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
            LEFT JOIN {$wpdb->prefix}postmeta pm_user ON p.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN (" . implode(',', array_fill(0, count($statuses), '%s')) . ")
            AND pm_user.meta_value = %d
            AND pm_total.meta_value > 0
            ORDER BY p.post_date_gmt " . $sorting /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The argument is safe. */ . "
            LIMIT 1
          ",
          array_merge(
            $statuses,
            [$customer->get_id()]
          )
        )
      );
    }

    return $date ? new DateTimeImmutable($date, new DateTimeZone('GMT')) : null;
  }

  private function getOrderProductTermIds(WC_Customer $customer, string $taxonomy, int $inTheLastSeconds = null): array {
    global $wpdb;

    $statuses = array_map(function (string $status) {
      return "wc-$status";
    }, $this->wooCommerce->wcGetIsPaidStatuses());
    $statusesPlaceholder = implode(',', array_fill(0, count($statuses), '%s'));

    // get all product categories that the customer has purchased
    if ($this->wooCommerce->isWooCommerceCustomOrdersTableEnabled()) {
      $inTheLastFilter = isset($inTheLastSeconds) ? 'AND o.date_created_gmt >= DATE_SUB(current_timestamp, INTERVAL %d SECOND)' : '';

      $orderIdsSubquery = "
        SELECT o.id
        FROM %i o
        WHERE o.status IN ($statusesPlaceholder)
        AND o.customer_id = %d
        $inTheLastFilter
      ";
      $orderIdsSubqueryArgs = array_merge(
        [$wpdb->prefix . 'wc_orders'],
        $statuses,
        [$customer->get_id()],
        $inTheLastSeconds ? [$inTheLastSeconds] : []
      );
    } else {
      $inTheLastFilter = isset($inTheLastSeconds) ? 'AND p.post_date_gmt >= DATE_SUB(current_timestamp, INTERVAL %d SECOND)' : '';

      $orderIdsSubquery = "
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_user ON p.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ($statusesPlaceholder)
        AND pm_user.meta_value = %d
        $inTheLastFilter
      ";
      $orderIdsSubqueryArgs = array_merge(
        $statuses,
        [$customer->get_id()],
        $inTheLastSeconds ? [$inTheLastSeconds] : []
      );
    }

    $result = $wpdb->get_col(
      // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The number of replacements is dynamic.
      $wpdb->prepare(
        "
          SELECT DISTINCT tt.term_id
          FROM {$wpdb->term_taxonomy} tt
          JOIN %i AS oi ON oi.order_id IN (" . $orderIdsSubquery . /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The subquery uses placeholders. */ ") AND oi.order_item_type = 'line_item'
          JOIN %i AS pid ON oi.order_item_id = pid.order_item_id AND pid.meta_key = '_product_id'
          JOIN {$wpdb->posts} p ON pid.meta_value = p.ID
          JOIN {$wpdb->term_relationships} tr ON IF(p.post_type = 'product_variation', p.post_parent, p.ID) = tr.object_id AND tr.term_taxonomy_id = tt.term_taxonomy_id
          WHERE tt.taxonomy = %s
          ORDER BY tt.term_id ASC
        ",
        array_merge(
          [$wpdb->prefix . 'woocommerce_order_items'],
          $orderIdsSubqueryArgs,
          [
            $wpdb->prefix . 'woocommerce_order_itemmeta',
            (string)($taxonomy),
          ]
        )
      )
    );
    return array_map('intval', $result);
  }

  private function isInTheLastSeconds(WC_Order $order, ?int $inTheLastSeconds): bool {
    if ($inTheLastSeconds === null) {
      return true;
    }
    return $order->get_date_created() >= new DateTimeImmutable("-$inTheLastSeconds seconds");
  }
}
