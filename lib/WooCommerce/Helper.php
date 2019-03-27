<?php
namespace MailPoet\WooCommerce;

class Helper {
  function isWooCommerceActive() {
    return class_exists('WooCommerce');
  }

  function wcGetCustomerOrderCount($user_id) {
    return wc_get_customer_order_count($user_id);
  }

  function wcGetOrder($order = false) {
    return wc_get_order($order);
  }

  function wcPrice($price, array $args = array()) {
    return wc_price($price, $args);
  }
}
