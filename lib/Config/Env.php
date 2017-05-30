<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Env {
  static $version;
  static $plugin_name;
  static $plugin_path;
  static $file;
  static $path;
  static $views_path;
  static $assets_path;
  static $assets_url;
  static $util_path;
  static $temp_path;
  static $cache_path;
  static $temp_url;
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
  static $db_collation;
  static $db_charset_collate;
  static $db_timezone_offset;
  static $required_permission = 'manage_options';

  static function init($file, $version) {
    global $wpdb;
    self::$version = $version;
    self::$file = $file;
    self::$path = dirname(self::$file);
    self::$plugin_name = 'mailpoet';
    self::$views_path = self::$path . '/views';
    self::$assets_path = self::$path . '/assets';
    self::$assets_url = plugins_url('/assets', $file);
    self::$util_path = self::$path . '/lib/Util';
    $wp_upload_dir = wp_upload_dir();
    self::$temp_path = $wp_upload_dir['basedir'] . '/' . self::$plugin_name;
    self::$cache_path = self::$temp_path . '/cache';
    self::$temp_url = $wp_upload_dir['baseurl'] . '/' . self::$plugin_name;
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
    self::$db_charset = $wpdb->charset;
    self::$db_collation = $wpdb->collate;
    self::$db_charset_collate = $wpdb->get_charset_collate();
    self::$db_source_name = self::dbSourceName(self::$db_host, self::$db_socket, self::$db_port, self::$db_charset);
    self::$db_timezone_offset = self::getDbTimezoneOffset();
  }

  private static function dbSourceName($host, $socket, $port, $charset) {
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
    if(!empty($charset)) {
      $source_name[] = ';charset=' . $charset;
    }
    return implode('', $source_name);
  }

  static function getDbTimezoneOffset($offset = false) {
    $offset = ($offset) ? $offset : get_option('gmt_offset');
    $mins = $offset * 60;
    $sgn = ($mins < 0 ? -1 : 1);
    $mins = abs($mins);
    $hrs = floor($mins / 60);
    $mins -= $hrs * 60;
    return sprintf('%+03d:%02d', $hrs * $sgn, $mins);
  }
}
