<?php

namespace MailPoet\Config;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Env {
  const NEWSLETTER_CONTENT_WIDTH = 1320;

  static $version;
  static $plugin_name;
  static $plugin_path;
  static $base_url;
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

  static function init($file, $version, $db_host, $db_user, $db_password, $db_name) {
    self::$version = $version;
    self::$file = $file;
    self::$path = dirname(self::$file);
    self::$plugin_name = 'mailpoet';
    self::$base_url = WPFunctions::get()->pluginsUrl('', $file);
    self::$views_path = self::$path . '/views';
    self::$assets_path = self::$path . '/assets';
    self::$assets_url = WPFunctions::get()->pluginsUrl('/assets', $file);
    self::$util_path = self::$path . '/lib/Util';
    $wp_upload_dir = WPFunctions::get()->wpUploadDir();
    self::$temp_path = $wp_upload_dir['basedir'] . '/' . self::$plugin_name;
    self::$cache_path = self::$temp_path . '/cache';
    self::$temp_url = $wp_upload_dir['baseurl'] . '/' . self::$plugin_name;
    self::$languages_path = self::$path . '/lang';
    self::$lib_path = self::$path . '/lib';
    self::$plugin_prefix = 'mailpoet_';
    self::initDbParameters($db_host, $db_user, $db_password, $db_name);
  }

  /**
   * @see https://codex.wordpress.org/Editing_wp-config.php#Set_Database_Host for possible DB_HOSTS values
   */
  private static function initDbParameters($db_host, $db_user, $db_password, $db_name) {
    global $wpdb;
    self::$db_prefix = $wpdb->prefix . self::$plugin_prefix;
    self::$db_host = $db_host;
    self::$db_port = 3306;
    self::$db_socket = false;
    // Peel off the port parameter
    if (preg_match('/(?=:\d+$)/', $db_host)) {
      list(self::$db_host, self::$db_port) = explode(':', $db_host);
    }
    // Peel off the socket parameter
    if (preg_match('/:\//', self::$db_host)) {
      list(self::$db_host, self::$db_socket) = explode(':', $db_host);
    }
    self::$db_name = $db_name;
    self::$db_username = $db_user;
    self::$db_password = $db_password;
    self::$db_charset = $wpdb->charset;
    self::$db_collation = $wpdb->collate;
    self::$db_charset_collate = $wpdb->get_charset_collate();
    self::$db_source_name = self::dbSourceName(self::$db_host, self::$db_socket, self::$db_port, self::$db_charset, self::$db_name);
    self::$db_timezone_offset = self::getDbTimezoneOffset();
  }

  private static function dbSourceName($host, $socket, $port, $charset, $db_name) {
    $source_name = array(
      'mysql:host=',
      $host,
      ';',
      'port=',
      $port,
      ';',
      'dbname=',
      $db_name
    );
    if (!empty($socket)) {
      $source_name[] = ';unix_socket=' . $socket;
    }
    if (!empty($charset)) {
      $source_name[] = ';charset=' . $charset;
    }
    return implode('', $source_name);
  }

  static function getDbTimezoneOffset($offset = false) {
    $offset = ($offset) ? $offset : WPFunctions::get()->getOption('gmt_offset');
    $mins = $offset * 60;
    $sgn = ($mins < 0 ? -1 : 1);
    $mins = abs($mins);
    $hrs = floor($mins / 60);
    $mins -= $hrs * 60;
    return sprintf('%+03d:%02d', $hrs * $sgn, $mins);
  }
}
