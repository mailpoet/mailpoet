<?php

// phpcs:ignore
class WooCommerce {
  public function mailer() {
  }
}

/**
* @return WooCommerce
*/
function WC() {
}

function wc_get_customer_order_count(int $user_id): int {
  return 0;
}

/**
* @param  mixed $order
* @return mixed
*/
function wc_get_order($order = false) {
  return false;
}

/**
* @param  mixed[] $args
* @return WC_Order[]|int[]|\stdClass
*/
function wc_get_orders($args) {
  return [];
}

function wc_price(float $price, array $args = []): string {
  return '';
}

/**
* @return string
*/
function get_woocommerce_currency() {
  return '';
}

function wc_light_or_dark(string $color, string $dark, string $light) {
  return '';
}

function wc_hex_is_light(string $color) {
  return false;
}

function wc_get_product($the_product = false, $deprecated = []) {
  return null;
}

// phpcs:ignore
class WC_DateTime extends \DateTime {
}

/**
* @method int get_id()
* @method WC_DateTime|null get_date_created(string $context = 'view')
* @method string get_billing_email(string $context = 'view')
* @method string get_currency(string $context = 'view')
* @method float get_total(string $context = 'view')
*/
class WC_Order { // phpcs:ignore
}

/**
 * @method int get_product_id(string $context = 'view')
 */
class WC_Order_Item_Product { // phpcs:ignore
  function get_product_id() { // phpcs:ignore
  }
}

/**
 * @method int[] get_category_ids(string $context = 'view')
 */
class WC_Product { // phpcs:ignore
}

class WC_Cart { // phpcs:ignore
}

class WC_Mailer { // phpcs:ignore
  function email_header() { // phpcs:ignore
  }
  function email_footer() { // phpcs:ignore
  }
}

if (!class_exists(WC_Session::class)) {
  /**
   * @method void init()
   * @method void cleanup_sessions()
   * @method mixed __get(mixed $key)
   * @method void __set(mixed $key, mixed $value)
   * @method bool __isset(mixed $key)
   * @method array|string get(string $key, mixed $default = null)
   * @method void set(string $key, mixed $value)
   * @method int get_customer_id()
   */
  class WC_Session { // phpcs:ignore
    /** @param mixed $key */
    public function __unset($key) {
    }
  }
}
