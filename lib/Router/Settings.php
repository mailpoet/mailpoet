<?php
namespace MailPoet\Router;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get($params) {

    $settingNames = array();
    if(count($params) > 1) {
      $settingNames = array_map(
        function ($setting) {
          if(isset($setting['name'])) return $setting['name'];
        },
        $params
      );
    } elseif(isset($params['name'])) $settingNames = array($params['name']);

    if(count($settingNames)) {
      return $this->returnResults(
        Setting::where_in('name', $settingNames)
          ->find_array()
      );
    }

    return $this->returnResults(array('error' => 'invalid_params'));

  }

  function set($params) {

  }

  private function returnResults($results) {
    if(php_sapi_name() === 'cli') {
      return json_encode($results);
    } else {
      wp_send_json($results);
    }
  }

}
