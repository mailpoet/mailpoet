<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Env {
  public static $plugin_name;
  public static $plugin_prefix;
  public static $db_prefix;
  public static $db_source_name;
  public static $db_host;
  public static $db_name;
  public static $db_username;
  public static $db_password;
  public static $db_charset;

  public static function init() {
    global $wpdb;
    self::$plugin_name = 'mailpoet';
    self::$plugin_prefix = 'mailpoet_';
    self::$db_prefix = $wpdb->prefix . self::$plugin_prefix;
    self::$db_source_name = self::dbSourceName();
    self::$db_host = DB_HOST;
    self::$db_name = DB_NAME;
    self::$db_username = DB_USER;
    self::$db_password = DB_PASSWORD;
    self::$db_charset = $wpdb->get_charset_collate();
  }

  public static function dbSourceName() {
    $source_name = array(
      'mysql:host=',
      DB_HOST,
      ';',
      'dbname=',
      DB_NAME
    );
    return implode('', $source_name);
  }
}
