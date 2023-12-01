<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use WC_Customer;

class CustomerReviewFieldsFactory {
  /** @var WordPress */
  private $wordPress;

  public function __construct(
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
  }

  /** @return Field[] */
  public function getFields(): array {
    return [
      new Field(
        'woocommerce:customer:review-count',
        Field::TYPE_INTEGER,
        __('Review count', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $this->getUniqueProductReviewCount($customer) : 0;
        }
      ),
      new Field(
        'woocommerce:customer:last-review-date',
        Field::TYPE_DATETIME,
        __('Last review date', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $this->getLastReviewDate($customer) : null;
        }
      ),
    ];
  }

  /**
   * Calculate the customer's review count excluding multiple reviews on the same product.
   * Inspired by AutomateWoo implementation.
   */
  private function getUniqueProductReviewCount(WC_Customer $customer): int {
    $wpdb = $this->wordPress->getWpdb();
    /** @var literal-string $sql */
    $sql = "
      SELECT COUNT(DISTINCT comment_post_ID) FROM {$wpdb->comments}
      WHERE comment_parent = 0
      AND comment_approved = 1
      AND comment_type = 'review'
      AND (user_ID = %d OR comment_author_email = %s)
    ";
    return (int)$wpdb->get_var(
      (string)$wpdb->prepare($sql, [$customer->get_id(), $customer->get_email()])
    );
  }

  private function getLastReviewDate(WC_Customer $customer): ?DateTimeImmutable {
    $wpdb = $this->wordPress->getWpdb();
    /** @var literal-string $sql */
    $sql = "
      SELECT comment_date FROM {$wpdb->comments}
      WHERE comment_parent = 0
      AND comment_approved = 1
      AND comment_type = 'review'
      AND (user_ID = %d OR comment_author_email = %s)
      ORDER BY comment_date DESC
      LIMIT 1
    ";

    $date = $wpdb->get_var(
      (string)$wpdb->prepare($sql, [$customer->get_id(), $customer->get_email()])
    );
    return $date ? new DateTimeImmutable($date, $this->wordPress->wpTimezone()) : null;
  }
}
