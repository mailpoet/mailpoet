<?php

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;

if (!defined('ABSPATH')) exit;

class Activator {

  /** @var SettingsController */
  private $settings;

  /** @var Populator */
  private $populator;

  function __construct(SettingsController $settings, Populator $populator) {
    $this->settings = $settings;
    $this->populator = $populator;
  }

  function activate() {
    $migrator = new Migrator();
    $migrator->up();

    $this->populator->up();
    $this->updateDbVersion();

    $caps = new Capabilities();
    $caps->setupWPCapabilities();
  }

  function deactivate() {
    $migrator = new Migrator();
    $migrator->down();

    $caps = new Capabilities();
    $caps->removeWPCapabilities();
  }

  function updateDbVersion() {
    try {
      $current_db_version = $this->settings->get('db_version');
    } catch (\Exception $e) {
      $current_db_version = null;
    }

    $this->settings->set('db_version', Env::$version);

    // if current db version and plugin version differ, log an update
    if (version_compare($current_db_version, Env::$version) !== 0) {
      $updates_log = (array)$this->settings->get('updates_log', []);
      $updates_log[] = [
        'previous_version' => $current_db_version,
        'new_version' => Env::$version,
        'date' => date('Y-m-d H:i:s'),
      ];
      $this->settings->set('updates_log', $updates_log);
    }
  }
}
