<?php

define('ABSPATH', getenv('WP_ROOT') . '/');

require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

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

function wc_price(float $price, array $args = []): string {
  return '';
}