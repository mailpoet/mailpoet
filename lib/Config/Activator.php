<?php

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;

if(!defined('ABSPATH')) exit;

class Activator {

  /** @var SettingsController */
  private $settings;

  function __construct(SettingsController $settings) {
    $this->settings = $settings;
  }

  function activate() {
    $migrator = new Migrator();
    $migrator->up();

    $populator = new Populator();
    $populator->up();
    $this->settings->set('db_version', Env::$version);

    $caps = new Capabilities();
    $caps->setupWPCapabilities();
  }

  function deactivate() {
    $migrator = new Migrator();
    $migrator->down();

    $caps = new Capabilities();
    $caps->removeWPCapabilities();
  }
}
