<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Env {
  static $version;
  static $plugin_name;
  static $plugin_url;
  static $plugin_path;
  static $file;
  static $path;
  static $views_path;
  static $assets_path;
  static $assets_url;
  static $temp_name;
  static $temp_path;
  static $languages_path;
  static $lib_path;
  static $plugin_prefix;
  static $db_prefix;
  static $db_source_name;
  static $db_host;
  static $db_socket;
  static $db_port;
  static $db_name;
  static $db_username;
  static $db_password;
  static $db_charset;

  static function init($file, $version) {
    global $wpdb;
    self::$version = $version;
    self::$file = $file;
    self::$path = dirname(self::$file);
    self::$plugin_name = 'mailpoet';
    self::$plugin_url = plugin_dir_url(__FILE__);
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
    if(preg_match('/(?=:\d+$)/', DB_HOST)) {
      list(self::$db_host, self::$db_port) = explode(':', DB_HOST);
    } else {
      if(preg_match('/:/', DB_HOST)) {
        self::$db_socket = true;
      }
    }
    self::$db_name = DB_NAME;
    self::$db_username = DB_USER;
    self::$db_password = DB_PASSWORD;
    self::$db_charset = $wpdb->get_charset_collate();
    self::$db_source_name = self::dbSourceName(self::$db_host, self::$db_socket, self::$db_port);
  }

  private static function dbSourceName($host, $socket, $port) {
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

  static function isPluginActivated() {
    $activatesPlugins = get_option('active_plugins');
    $isActivated = (
      in_array(
        sprintf('%s/%s.php', basename(self::$path), self::$plugin_name),
        $activatesPlugins
      ) ||
      in_array(
        sprintf('%s/%s.php', explode('/', plugin_basename(__FILE__))[0], self::$plugin_name),
        $activatesPlugins
      )
    );
    return ($isActivated) ? true : false;
  }
}