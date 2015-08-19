<?php
namespace MailPoet\Router;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get($params) {
    $settings = $this->filterParams($params);
    if(!$settings) return $this->returnInvalidParamsError();

    $getSettings = Setting::where_in('name', array_map(function ($setting) { return $setting['name']; }, $settings))
      ->find_array();
    return $this->returnResults($getSettings);
  }

  function set($params) {
    $settings = $this->filterParams($params);
    if(!$settings) return $this->returnInvalidParamsError();

    $createOrUpdateSettings = Setting::filter('createOrUpdate', $settings);
    $this->returnResults($createOrUpdateSettings);
  }

  private function filterParams($params) {
    $validParamsWithNames = array();
    if(isset($params[0])) {
      $validParamsWithNames = array_map(
        function ($setting) {
          if(isset($setting['name'])) return $setting;
        },
        $params
      );
    } elseif(isset($params['name'])) $validParamsWithNames = array($params);
    return ($validParamsWithNames) ? $validParamsWithNames : false;
  }

  private function returnResults($results) {
    if(php_sapi_name() === 'cli') {
      return json_encode($results);
    } else {
      wp_send_json($results);
    }
  }

  private function returnInvalidParamsError() {
    return $this->returnResults(array('error' => 'invalid_params'));
  }
}
