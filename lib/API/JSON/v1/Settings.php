<?php
namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class Settings extends APIEndpoint {
  function get() {
    return $this->successResponse(Setting::getAll());
  }

  function set($settings = array()) {
    if(empty($settings)) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST =>
          __('You have not specified any settings to be saved.', 'mailpoet')
      ));
    } else {
      foreach($settings as $name => $value) {
        Setting::setValue($name, $value);
      }
      $bridge = new Bridge();
      $bridge->onSettingsSave($settings);
      return $this->successResponse(Setting::getAll());
    }
  }
}