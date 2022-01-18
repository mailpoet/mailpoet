<?php

namespace MailPoet\Config;

use MailPoet\InvalidStateException;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class Activator {
  public const TRANSIENT_ACTIVATE_KEY = 'mailpoet_activator_activate';
  private const TRANSIENT_EXPIRATION = 120; // seconds

  /** @var SettingsController */
  private $settings;

  /** @var Populator */
  private $populator;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SettingsController $settings,
    Populator $populator,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->populator = $populator;
    $this->wp = $wp;
  }

  public function activate() {
    $isRunning = $this->wp->getTransient(self::TRANSIENT_ACTIVATE_KEY);
    if ($isRunning === false) {
      $this->wp->setTransient(self::TRANSIENT_ACTIVATE_KEY, '1', self::TRANSIENT_EXPIRATION);
      try {
        $this->processActivate();
      } finally {
        $this->wp->deleteTransient(self::TRANSIENT_ACTIVATE_KEY);
      }
    } else {
      throw new InvalidStateException(__('MailPoet version update is in progress, please refresh the page in a minute.', 'mailpoet'));
    }
  }

  private function processActivate(): void {
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
    if (version_compare((string)$currentDbVersion, Env::$version) !== 0) {
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
