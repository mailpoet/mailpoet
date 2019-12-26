<?php

namespace MailPoet\WooCommerce;

class Helper {

  public function isWooCommerceActive() {
    return class_exists('WooCommerce');
  }

  public function WC() {
    return WC();
  }

  public function wcGetCustomerOrderCount($user_id) {
    return wc_get_customer_order_count($user_id);
  }

  public function wcGetOrder($order = false) {
    return wc_get_order($order);
  }

  public function wcGetOrders(array $args) {
    return wc_get_orders($args);
  }

  public function wcPrice($price, array $args = []) {
    return wc_price($price, $args);
  }

  public function wcGetProduct($the_product = false) {
    return wc_get_product($the_product);
  }

  public function getWoocommerceCurrency() {
    return get_woocommerce_currency();
  }

  public function wcLightOrDark($color, $dark, $light) {
    return wc_light_or_dark($color, $dark, $light);
  }

  public function wcHexIsLight($color) {
    return wc_hex_is_light($color);
  }

  public function getOrdersCountCreatedBefore($date_time) {
    global $wpdb;
    $result = $wpdb->get_var( "
        SELECT DISTINCT count(p.ID) FROM {$wpdb->prefix}posts as p
        WHERE p.post_type = 'shop_order' AND p.post_date < '{$date_time}'
    " );
    return (int)$result;
  }

  public function getRawPrice($price, array $args = []) {
    $html_price = $this->wcPrice($price, $args);
    return html_entity_decode(strip_tags($html_price));
  }
}
