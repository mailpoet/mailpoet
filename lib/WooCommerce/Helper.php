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

  function WC() {
    return WC();
  }

  function wcGetCustomerOrderCount($user_id) {
    return wc_get_customer_order_count($user_id);
  }

  function wcGetOrder($order = false) {
    return wc_get_order($order);
  }

  function wcGetOrders(array $args) {
    return wc_get_orders($args);
  }

  function wcPrice($price, array $args = []) {
    return wc_price($price, $args);
  }

  function wcGetProduct($the_product = false) {
    return wc_get_product($the_product);
  }

  function getWoocommerceCurrency() {
    return get_woocommerce_currency();
  }

  function getOrdersCount() {
    $counts = $this->wp->wpCountPosts('shop_order');
    return array_reduce((array)$counts, function($sum, $count_for_state) {
      return $sum + (int)$count_for_state;
    });
  }

  function getRawPrice($price, array $args = []) {
    $html_price = $this->wcPrice($price, $args);
    return html_entity_decode(strip_tags($html_price));
  }
}
