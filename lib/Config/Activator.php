<?php

namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Activator {
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