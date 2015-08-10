<?php
namespace MailPoet\Config;
use \MailPoet\Config\Migrator;

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
  }
}
