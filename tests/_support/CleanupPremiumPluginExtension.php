<?php

use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;

class CleanupPremiumPluginExtension extends Extension { // phpcs:ignore PSR1.Classes.ClassDeclaration
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
