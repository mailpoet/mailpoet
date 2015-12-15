<?php
namespace MailPoet\Config;
use \MailPoet\Config\Migrator;
use \MailPoet\Config\Populator;

if(!defined('ABSPATH')) exit;

class Activator {
  function __construct() {
  }

  function init() {
    register_activation_hook(
      Env::$file,
      array($this, 'activate')
    );
  }

  function activate() {
    $migrator = new Migrator();
    $migrator->up();

    $populator = new Populator();
    $populator->up();
  }

  function deactivate() {
    $migrator = new Migrator();
    $migrator->down();
  }
}
