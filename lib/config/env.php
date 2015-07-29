<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Env {
  public static $db_prefix;
  public static $db_host;
  public static $db_username;
  public static $db_password;

  public static function init() {
    global $wpdb;
    self::$db_prefix = $wpdb->prefix;
    self::$db_host = DB_HOST;
    self::$db_username = DB_USER;
    self::$db_password = DB_PASSWORD;
  }
}
