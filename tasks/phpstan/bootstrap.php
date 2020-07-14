<?php

define('ABSPATH', getenv('WP_ROOT') . '/');

require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once(ABSPATH . 'wp-admin/includes/ms.php');

use MailPoet\Mailer\WordPress\PHPMailerLoader;

PHPMailerLoader::load();

if (!class_exists('\MailPoet\Premium\DI\ContainerConfigurator')) {
  require_once __DIR__ . '/PremiumContainerConfigurator.php';
}

if (!class_exists(WooCommerce::class)) {
  require_once __DIR__ . '/woocommerce.php';
}

require_once __DIR__ . '/function-stubs.php';

// methods & classes for Premium plugin installation are required
// only when needed so we need to let PHPStan know about them
if (!function_exists('plugins_api')) {
  /**
   * @param string $action
   * @param array|object $args
   * @return object|array|WP_Error
   */
  function plugins_api($action, $args) {
    return [];
  }
}

if (!class_exists(WP_Ajax_Upgrader_Skin::class)) {
  // phpcs:ignore
  class WP_Ajax_Upgrader_Skin {}
}

if (!class_exists(Plugin_Upgrader::class)) {
  // phpcs:ignore
  class Plugin_Upgrader {
    public function __construct($skin = null) {
    }

    /**
     * @param string $package
     * @param array $args
     * @return bool|WP_Error
     */
    public function install($package, $args = []) {
      return true;
    }
  }
}
