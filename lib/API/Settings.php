<?php
namespace MailPoet\API;

use \MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get() {
    $settings = Setting::getAll();
    return $settings;
  }

  function set($settings = array()) {
    if(empty($settings)) {
      return false;
    } else {
      foreach($settings as $name => $value) {
        Setting::setValue($name, $value);
      }
      return true;
    }
  }
}
