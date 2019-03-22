<?php
namespace MailPoet\WooCommerce;

use MailPoet\WP\Functions;

class Helper {

  /** @var Functions */
  private $wp;

  function __construct(Functions $wp = null) {
    if (!$wp) {
      $wp = Functions::get();
    }
    $this->wp = $wp;
  }

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

  function wcGetProduct($the_product = false) {
    return wc_get_product($the_product);
  }

  function getOrdersCount() {
    $counts = $this->wp->wpCountPosts('shop_order');
    return array_reduce((array)$counts, function($sum, $count_for_state) {
      return $sum + (int)$count_for_state;
    });
  }
}
