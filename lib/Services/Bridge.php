<?php
namespace MailPoet\Services;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Bridge {
  const API_KEY_STATE_SETTING_NAME = 'mta.mailpoet_api_key_state';

  const MAILPOET_KEY_VALID = 200;
  const MAILPOET_KEY_INVALID = 401;
  const MAILPOET_KEY_EXPIRING = 402;

  public $api;

  function __construct() {
  }

  static function isMPSendingServiceEnabled() {
    try {
      $mailer_config = Mailer::getMailerConfig();
      return !empty($mailer_config['method'])
        && $mailer_config['method'] === Mailer::METHOD_MAILPOET;
    } catch (\Exception $e) {
      return false;
    }
  }

  function initApi($api_key) {
    if($this->api) {
      $this->api->api_key = $api_key;
    } else {
      $this->api = new Bridge\API($api_key);
    }
  }

  function checkKey($api_key) {
    $this->initApi($api_key);
    $result = $this->api->checkKey();
    return $this->processResult($result);
  }

  function processResult(array $result) {
    if(empty($result['code'])) {
      return false;
    }
    Setting::setValue(
      self::API_KEY_STATE_SETTING_NAME,
      array(
        'code' => (int)$result['code'],
        'data' => !empty($result['data']) ? $result['data'] : null,
      )
    );
    return $result;
  }

  static function invalidateKey() {
    Setting::setValue(self::API_KEY_STATE_SETTING_NAME, array('code' => 401));
  }
}
