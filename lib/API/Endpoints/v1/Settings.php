<?php
namespace MailPoet\API\Endpoints\v1;

use MailPoet\API\Endpoint as APIEndpoint;
use MailPoet\API\Error as APIError;
use MailPoet\Mailer\Mailer as MailerConfig;
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
      if(!empty($settings[MailerConfig::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'])
          && Bridge::isMPSendingServiceEnabled()
      ) {
        $bridge = new Bridge();
        $result = $bridge->checkKey($settings[MailerConfig::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key']);
        $bridge->updateSubscriberCount($result);
      }
      return $this->successResponse(Setting::getAll());
    }
  }
}