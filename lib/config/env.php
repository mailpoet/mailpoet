<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Env {
  public static $plugin_prefix;
  public static $db_prefix;
  public static $db_host;
  public static $db_username;
  public static $db_password;
  public static $db_charset;

  public static function init() {
    global $wpdb;
    self::$plugin_prefix = 'mailpoet_';
    self::$db_prefix = $wpdb->prefix;
    self::$db_host = DB_HOST;
    self::$db_username = DB_USER;
    self::$db_password = DB_PASSWORD;
    self::$db_charset = $wpdb->get_charset_collate();
  }
}
