<?php declare(strict_types = 1);

namespace MailPoet\TestsSupport;

use Codeception\Events;
use Codeception\Extension;

class PluginsExtension extends Extension {

  public static $events = [
    Events::SUITE_BEFORE => 'setupInitialPluginsState',
  ];

  public function setupInitialPluginsState() {
    exec('wp plugin deactivate woo-gutenberg-products-block --allow-root');
    exec('wp plugin deactivate woocommerce-memberships --allow-root');
    exec('wp plugin deactivate woocommerce-subscriptions --allow-root');
    exec('wp plugin deactivate woocommerce --allow-root');
  }
}
