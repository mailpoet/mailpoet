<?php
namespace MailPoet\API\Endpoints;
use \MailPoet\API\Endpoint as APIEndpoint;
use \MailPoet\API\Error as APIError;
use \MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Settings extends APIEndpoint {
  function get() {
    return $this->successResponse(Setting::getAll());
  }

  function set($settings = array()) {
    if(empty($settings)) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST =>
          __("You have not specified any settings to be saved.", 'mailpoet')
      ));
    } else {
      foreach($settings as $name => $value) {
        Setting::setValue($name, $value);
      }
      return $this->successResponse(Setting::getAll());
    }
  }
}
