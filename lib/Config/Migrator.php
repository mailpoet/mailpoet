<?php
namespace MailPoet\Config;
use \MailPoet\Config\Env;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Migrator {
  function __construct() {
    $this->prefix = Env::$db_prefix;
    $this->charset = Env::$db_charset;
    $this->models = array(
      'subscribers',
      'settings'
    );
  }

  function up() {
    global $wpdb;

    $_this =  $this;
    $migrate = function($model) use ($_this) {
      dbDelta($_this->$model());
    };

    array_map($migrate, $this->models);
  }

  function down() {
    global $wpdb;

    $drop_table = function($model) {
      $table = $this->prefix . $model;
      $wpdb->query("DROP TABLE {$table}");
    };

    array_map($drop_table, $this->models);
  }

  function subscribers() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'first_name tinytext NOT NULL,',
      'last_name tinytext NOT NULL,',
      'email varchar(150) NOT NULL,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY email (email)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function settings() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(20) NOT NULL,',
      'value varchar(255) NOT NULL,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  private function sqlify($model, $attributes) {
    $table = $this->prefix . $model;

    $sql = array();
    $sql[] = "CREATE TABLE " . $table . " (";
    $sql = array_merge($sql, $attributes);
    $sql[] = ")" . $this->charset .  ";";

    return implode("\n", $sql);
  }
}
