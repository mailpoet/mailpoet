<?php

use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;

// phpcs:ignore PSR1.Classes.ClassDeclaration
class CleanupPremiumPluginExtension extends Extension {
  public static $events = [
    Events::TEST_BEFORE => 'cleanupPremiumPlugin',
  ];

  public function cleanupPremiumPlugin(TestEvent $event) {
    $premiumPluginPath = ABSPATH . 'wp-content/plugins/mailpoet-premium';
    if (file_exists($premiumPluginPath)) {
      exec("rm -rf $premiumPluginPath", $output);
    }
  }
}
