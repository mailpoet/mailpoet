<?php
namespace MailPoet\API\Endpoints;
use \MailPoet\API\Endpoint as APIEndpoint;
use \MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings extends APIEndpoint {
  function __construct() {
  }

  function get() {
    $settings = Setting::getAll();
    return $this->successResponse($settings);
  }

  function set($settings = array()) {
    if(empty($settings)) {
      return $this->badRequest();
    } else {
      foreach($settings as $name => $value) {
        Setting::setValue($name, $value);
      }
      return $this->successResponse();
    }
  }
}
