<?php
namespace MailPoet\Router;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get($params) {
    $settingNames = array();
    if(isset($params[0])) {
      $settingNames = array_map(
        function ($setting) {
          if(isset($setting['name'])) return $setting['name'];
        },
        $params
      );
    } elseif(isset($params['name'])) $settingNames = array($params['name']);

    if(!count($settingNames)) return $this->invalidParamsError();

    $settings = Setting::where_in('name', $settingNames)
      ->find_array();
    return $this->returnResults($settings);
  }

  function set($params) {
    $settings = array();
    if(isset($params[0])) {
      $settings = array_map(
        function ($setting) {
          if(isset($setting['name'])) return $setting;
        },
        $params
      );
    } elseif(isset($params['name'])) $settings = array($params);

    if(!count($settings)) return $this->invalidParamsError();

    $createOrUpdateSettings = Setting::filter('createOrUpdate', $settings);
    $this->returnResults($createOrUpdateSettings);
  }

  private function returnResults($results) {
    if(php_sapi_name() === 'cli') {
      return json_encode($results);
    } else {
      wp_send_json($results);
    }
  }

  private function invalidParamsError() {
    return $this->returnResults(array('error' => 'invalid_params'));
  }
}
