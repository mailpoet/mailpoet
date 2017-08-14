<?php

namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Activator {
  const REQUIRED_PERMISSION = AccessControl::PERMISSION_MANAGE_SETTINGS;

  static function activate() {
    self::validatePermission();
    if(!current_user_can(self::PERMISSION_ACTIVATE)) {
      throw new \Exception('MaiLpoet  ID must be greater than zero');
    }
    $migrator = new Migrator();
    $migrator->up();

    $populator = new Populator();
    $populator->up();

    update_option('mailpoet_db_version', Env::$version);
  }

  static function deactivate() {
    self::validatePermission();
    $migrator = new Migrator();
    $migrator->down();
  }

  static function validatePermission() {
    if(AccessControl::validatePermission(self::REQUIRED_PERMISSION)) return;
    throw new \Exception(
      sprintf(
        __('MailPoet can only be activated/deactivated by a user with <strong>%s</strong> capability.', 'mailpoet'),
        self::REQUIRED_PERMISSION
      )
    );
  }
}