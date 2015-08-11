<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Env {
  public static $version;
  public static $plugin_name;
  public static $file;
  public static $path;
  public static $views_path;
  public static $assets_path;
  public static $assets_url;
  public static $languages_path;
  public static $lib_path;
  public static $plugin_prefix;
  public static $db_prefix;
  public static $db_source_name;
  public static $db_host;
  public static $db_name;
  public static $db_username;
  public static $db_password;
  public static $db_charset;

  public static function init($file, $version) {
    global $wpdb;
    self::$version = $version;
    self::$plugin_name = 'mailpoet';
    self::$file = $file;
    self::$path = dirname(self::$file);
    self::$views_path = self::$path . '/views';
    self::$assets_path = self::$path . '/assets';
    self::$assets_url = plugins_url('/assets', $file);
    self::$languages_path = self::$path . '/lang';
    self::$lib_path = self::$path . '/lib';
    self::$plugin_prefix = 'mailpoet_';
    self::$db_prefix = $wpdb->prefix . self::$plugin_prefix;
    self::$db_source_name = self::dbSourceName();
    self::$db_host = DB_HOST;
    self::$db_name = DB_NAME;
    self::$db_username = DB_USER;
    self::$db_password = DB_PASSWORD;
    self::$db_charset = $wpdb->get_charset_collate();
  }

  private static function dbSourceName() {
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
