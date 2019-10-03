<?php

namespace MailPoet\AutomaticEmails\WooCommerce\WooCommerceStubs;

require_once __DIR__ . '/ItemDetails.php';

// phpcs:disable PSR1.Methods.CamelCapsMethodName
class OrderDetails
{
  public $total;
  public $order_id;

  function __construct($order_id = null) {
    $this->order_id = $order_id;
  }

  function get_customer_id() {
  }

  function get_billing_email() {
  }

  function get_date_created() {
  }

  function get_id() {
    return $this->order_id;
  }

  function get_total() {
    return $this->total;
  }

  function get_items() {
    return new ItemDetails();
  }
}
