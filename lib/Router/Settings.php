<?php
namespace MailPoet\Router;
use \MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get() {
    $settings = Setting::getAll();
    wp_send_json($settings);
  }

  function set($settings = array()) {
    if(empty($settings)) {
      wp_send_json(false);
    } else {
      foreach($settings as $name => $value) {
        if(is_array($value)) {
          $value = serialize($value);
        }
        Setting::createOrUpdate(array(
          'name' => $name,
          'value' => $value
        ));
      }
      wp_send_json(true);
    }
  }
}
