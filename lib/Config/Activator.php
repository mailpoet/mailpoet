<?php

namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Activator {
  private $access_control;

  function __construct(AccessControl $access_control) {
    $this->access_control = $access_control;
    if(!$this->access_control->validatePermission(AccessControl::PERMISSION_UPDATE_PLUGIN)) {
      throw new \Exception(__('You do not have permission to activate/deactivate MailPoet plugin.', 'mailpoet'));
    }
  }

  function activate() {
    $migrator = new Migrator();
    $migrator->up();

    $populator = new Populator();
    $populator->up();

    update_option('mailpoet_db_version', Env::$version);
  }

  function deactivate() {
    $migrator = new Migrator();
    $migrator->down();
  }
}