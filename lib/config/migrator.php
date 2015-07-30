<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Migrator {
  function __construct() {
    $this->prefix = \MailPoet\Config\Env::$db_prefix;
  }

  function up() {

  }
}
