<?php

namespace MailPoet\WooCommerce;

class Helper {
  public function isWooCommerceActive() {
    return class_exists('WooCommerce');
  }

  public function WC() {
    return WC();
  }

  public function wcGetCustomerOrderCount($userId) {
    return wc_get_customer_order_count($userId);
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

  public function wcGetProduct($theProduct = false) {
    return wc_get_product($theProduct);
  }

  public function getWoocommerceCurrency() {
    return get_woocommerce_currency();
  }

  public function woocommerceFormField($key, $args, $value) {
    return woocommerce_form_field($key, $args, $value);
  }

  public function wcLightOrDark($color, $dark, $light) {
    return wc_light_or_dark($color, $dark, $light);
  }

  public function wcHexIsLight($color) {
    return wc_hex_is_light($color);
  }

  public function getOrdersCountCreatedBefore($dateTime) {
    global $wpdb;
    $result = $wpdb->get_var( "
        SELECT DISTINCT count(p.ID) FROM {$wpdb->prefix}posts as p
        WHERE p.post_type = 'shop_order' AND p.post_date < '{$dateTime}'
    " );
    return (int)$result;
  }

  public function getRawPrice($price, array $args = []) {
    $htmlPrice = $this->wcPrice($price, $args);
    return html_entity_decode(strip_tags($htmlPrice));
  }
}
