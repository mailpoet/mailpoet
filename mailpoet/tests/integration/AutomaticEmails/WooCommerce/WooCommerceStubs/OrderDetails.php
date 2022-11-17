<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs;

require_once __DIR__ . '/ItemDetails.php';

// phpcs:disable PSR1.Methods.CamelCapsMethodName
class OrderDetails {

  public $total;
  public $orderId;

  public function __construct(
    $orderId = null
  ) {
    $this->orderId = $orderId;
  }

  public function get_customer_id() {
  }

  public function get_billing_email() {
  }

  public function get_date_created() {
  }

  public function get_id() {
    return $this->orderId;
  }

  public function get_total() {
    return $this->total;
  }

  public function get_items() {
    return new ItemDetails();
  }
}
