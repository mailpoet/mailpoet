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
  static $db_host;
  static $db_is_ipv6;
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
    self::$plugin_path = 'mailpoet/mailpoet.php';
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
    $parsed_host = WPFunctions::get()->parseDbHost($db_host);
    if ($parsed_host === false) {
      throw new \InvalidArgumentException('Invalid db host configuration.');
    }
    list($host, $port, $socket, $is_ipv6) = $parsed_host;

    global $wpdb;
    self::$db_prefix = $wpdb->prefix . self::$plugin_prefix;
    self::$db_host = $host;
    self::$db_is_ipv6 = $is_ipv6;
    self::$db_port = $port ?: 3306;
    self::$db_socket = $socket;
    self::$db_name = $db_name;
    self::$db_username = $db_user;
    self::$db_password = $db_password;
    self::$db_charset = $wpdb->charset;
    self::$db_collation = $wpdb->collate;
    self::$db_charset_collate = $wpdb->get_charset_collate();
    self::$db_timezone_offset = self::getDbTimezoneOffset();
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
