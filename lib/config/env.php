<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Env {
  public static $db_prefix;

  public static function init() {
    global $wpdb;
    self::$db_prefix = $wpdb->prefix;
  }
}
