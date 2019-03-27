<?php
namespace MailPoet\WooCommerce;

class Helper {
  function isWooCommerceActive() {
    return class_exists('WooCommerce');
  }
  function wcGetCustomerOrderCount($user_id) {
    if (! is_callable('wc_get_customer_order_count')) {
      throw new \Exception("function 'wc_get_customer_order_count' not found!");
    }
    return wc_get_customer_order_count($user_id);
  }
  function wcGetOrder($order = false) {
    if (! is_callable('wc_get_order')) {
      throw new \Exception("function 'wc_get_order' not found!");

    }
    return wc_get_order($order);
  }

  function wcPrice($price, array $args = array()) {
    if (! is_callable('wc_price')) {
      throw new \Exception("function 'wc_price' not found!");

    }
    return wc_price($price, $args);
  }
}
