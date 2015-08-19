<?php
namespace MailPoet\Router;
use \MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get() {
    $settings = Setting::find_array();
    wp_send_json($settings);
  }

  function set($args) {
    $save = function($setting) {
      Setting::createOrUpdate($setting);
    };
    $results = array_map($save, $args);

    wp_send_json(in_array(false, $results));
  }
}
