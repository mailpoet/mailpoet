<?php

namespace MailPoet\WooCommerce;

class Helper {

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

  function getOrdersCountCreatedBefore($date_time) {
    global $wpdb;
    $result = $wpdb->get_var( "
        SELECT DISTINCT count(p.ID) FROM {$wpdb->prefix}posts as p
        WHERE p.post_type = 'shop_order' AND p.post_date < '{$date_time}'
    " );
    return (int)$result;
  }

  function getRawPrice($price, array $args = []) {
    $html_price = $this->wcPrice($price, $args);
    return html_entity_decode(strip_tags($html_price));
  }
}
