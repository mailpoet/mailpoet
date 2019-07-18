<?php

use \Codeception\Events;
use MailPoet\Test\DataFactories\Settings;

class SettingsExtension extends \Codeception\Extension {

  public static $events = [
    Events::SUITE_BEFORE => 'beforeSuite',
  ];

  public function beforeSuite(\Codeception\Event\SuiteEvent $e) {
    $settings = new Settings();
    $settings->withDefaultSettings();
  }

}
