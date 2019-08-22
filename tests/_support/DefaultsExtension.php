<?php

use \Codeception\Events;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\UserFlags;

class DefaultsExtension extends \Codeception\Extension {

  public static $events = [
    Events::SUITE_BEFORE => 'setupDefaults',
  ];

  public function setupDefaults(\Codeception\Event\SuiteEvent $e) {
    $settings = new Settings();
    $settings->withDefaultSettings();

    $user_flags = new UserFlags(1);
    $user_flags->withDefaultFlags();
  }

}
