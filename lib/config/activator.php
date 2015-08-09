<?php
namespace MailPoet\Config;
use \MailPoet\Config\Migrator;

if(!defined('ABSPATH')) exit;

class Activator {
  function __construct() {
  }

  function init() {
    $this->migrator = new Migrator;
  }

  function register_activation() {
    register_activation_hook(
      Env::$file,
      array($this, 'activate')
    );
  }

  public function activate() {
    $this->migrator->up();
  }
}
