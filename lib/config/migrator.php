<?php
namespace MailPoet\Config;
use \MailPoet\Config\Env;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Migrator {
  function __construct() {
    $this->prefix = Env::$db_prefix . 'mailpoet_';
    $this->charset = Env::$db_charset;
  }

  function up() {
    global $wpdb;
    dbDelta($this->subscriber());
  }

  function subscriber() {
    $table = $this->prefix . 'subscriber';
    $sql = "CREATE TABLE " . $table . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      name text NOT NULL,
      PRIMARY KEY  (id)
    );";
    return $sql;
  }
}
