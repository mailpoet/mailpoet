<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

if(!defined('ABSPATH')) exit;

class Settings extends APIEndpoint {

  /** @var SettingsController */
  private $settings;

  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS
  );

  function __construct(SettingsController $settings) {
    $this->settings = $settings;
  }

  function get() {
    return $this->successResponse($this->settings->getAll());
  }

  function set($settings = array()) {
    if(empty($settings)) {
      return $this->badRequest(
        array(
          APIError::BAD_REQUEST =>
            __('You have not specified any settings to be saved.', 'mailpoet')
        ));
    } else {
      foreach($settings as $name => $value) {
        $this->settings->set($name, $value);
      }
      $bridge = new Bridge();
      $bridge->onSettingsSave($settings);
      return $this->successResponse($this->settings->getAll());
    }
  }
}
