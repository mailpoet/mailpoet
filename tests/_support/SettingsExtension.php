<?php

use \Codeception\Events;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\UserFlags;

class SettingsExtension extends \Codeception\Extension {

  public static $events = [
    Events::SUITE_BEFORE => 'beforeSuite',
  ];

  public function beforeSuite(\Codeception\Event\SuiteEvent $e) {
    $settings = new Settings();
    $settings->withDefaultSettings();

    $user_flags = new UserFlags(1);
    $user_flags->withDefaultFlags();
  }

}
