<?php

// phpcs:ignore
class WooCommerce {
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
