<?php

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;

class Activator {

  /** @var SettingsController */
  private $settings;

  /** @var Populator */
  private $populator;

  public function __construct(SettingsController $settings, Populator $populator) {
    $this->settings = $settings;
    $this->populator = $populator;
  }

  public function activate() {
    $migrator = new Migrator();
    $migrator->up();

    $this->populator->up();
    $this->updateDbVersion();

    $caps = new Capabilities();
    $caps->setupWPCapabilities();
  }

  public function deactivate() {
    $migrator = new Migrator();
    $migrator->down();

    $caps = new Capabilities();
    $caps->removeWPCapabilities();
  }

  public function updateDbVersion() {
    try {
      $currentDbVersion = $this->settings->get('db_version');
    } catch (\Exception $e) {
      $currentDbVersion = null;
    }

    $this->settings->set('db_version', Env::$version);

    // if current db version and plugin version differ, log an update
    if (version_compare($currentDbVersion, Env::$version) !== 0) {
      $updatesLog = (array)$this->settings->get('updates_log', []);
      $updatesLog[] = [
        'previous_version' => $currentDbVersion,
        'new_version' => Env::$version,
        'date' => date('Y-m-d H:i:s'),
      ];
      $this->settings->set('updates_log', $updatesLog);
    }
  }
}
