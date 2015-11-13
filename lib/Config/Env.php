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
  public static $temp_name;
  public static $temp_path;
  public static $languages_path;
  public static $lib_path;
  public static $plugin_prefix;
  public static $db_prefix;
  public static $db_source_name;
  public static $db_host;
  public static $db_socket;
  public static $db_port;
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
    self::$temp_name = 'temp';
    self::$temp_path = self::$path . '/' . self::$temp_name;
    self::$languages_path = self::$path . '/lang';
    self::$lib_path = self::$path . '/lib';
    self::$plugin_prefix = 'mailpoet_';
    self::$db_prefix = $wpdb->prefix . self::$plugin_prefix;
    self::$db_host = DB_HOST;
    self::$db_port = 3306;
    self::$db_socket = false;
    if (preg_match('/(?=:\d+$)/', DB_HOST)) {
      list(self::$db_host, self::$db_port) = explode(':', DB_HOST);
    }
    else if (preg_match('/:/', DB_HOST)) {
      self::$db_socket = true;
    }
    self::$db_name = DB_NAME;
    self::$db_username = DB_USER;
    self::$db_password = DB_PASSWORD;
    self::$db_charset = $wpdb->get_charset_collate();
    self::$db_source_name = self::dbSourceName(self::$db_host, self::$db_socket, self::$db_port);
  }

  private static function dbSourceName($host, $socket,$port) {
    $source_name = array(
      (!$socket) ? 'mysql:host=' : 'mysql:unix_socket=',
      $host,
      ';',
      'port=',
      $port,
      ';',
      'dbname=',
      DB_NAME
    );
    return implode('', $source_name);
  }
}