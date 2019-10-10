<?php

define('ABSPATH', getenv('WP_ROOT') . '/');

require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . '/wp-includes/class-phpmailer.php';

if (!class_exists('\MailPoet\Premium\DI\ContainerConfigurator')) {
  require_once './PremiumContainerConfigurator.php';
}

if (!class_exists(WooCommerce::class)) {
  require_once __DIR__ . '/woocommerce.php';
}
