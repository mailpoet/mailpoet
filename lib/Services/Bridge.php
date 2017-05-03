<?php
namespace MailPoet\Services;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Bridge {
  const API_KEY_STATE_SETTING_NAME = 'mta.mailpoet_api_key_state';
  const PREMIUM_KEY_STATE_SETTING_NAME = 'premium.premium_key_state';

  const MAILPOET_KEY_VALID = 'valid';
  const MAILPOET_KEY_INVALID = 'invalid';
  const MAILPOET_KEY_EXPIRING = 'expiring';

  const MAILPOET_KEY_CHECK_ERROR = 'check_error';

  const PREMIUM_KEY_VALID = 'valid';
  const PREMIUM_KEY_INVALID = 'invalid';
  const PREMIUM_KEY_EXPIRING = 'expiring';
  const PREMIUM_KEY_USED = 'used';

  const PREMIUM_KEY_CHECK_ERROR = 'check_error';

  const CHECK_ERROR_UNAVAILABLE = 503;
  const CHECK_ERROR_UNKNOWN = 'unknown';

  public $api;

  static function isMPSendingServiceEnabled() {
    try {
      $mailer_config = Mailer::getMailerConfig();
      return !empty($mailer_config['method'])
        && $mailer_config['method'] === Mailer::METHOD_MAILPOET;
    } catch (\Exception $e) {
      return false;
    }
  }

  static function isPremiumKeySpecified() {
    $key = Setting::getValue(self::PREMIUM_KEY_STATE_SETTING_NAME);
    return !empty($key);
  }

  function initApi($api_key) {
    if($this->api) {
      $this->api->setKey($api_key);
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
    $state_map = array(
      200 => self::MAILPOET_KEY_VALID,
      401 => self::MAILPOET_KEY_INVALID,
      402 => self::MAILPOET_KEY_EXPIRING
    );

    $update_settings = false;

    if(!empty($result['code']) && isset($state_map[$result['code']])) {
      $key_state = $state_map[$result['code']];
      $update_settings = true;
    } else {
      $key_state = self::MAILPOET_KEY_CHECK_ERROR;
    }

    $state = array(
      'state' => $key_state,
      'data' => !empty($result['data']) ? $result['data'] : null,
      'code' => !empty($result['code']) ? $result['code'] : self::CHECK_ERROR_UNKNOWN
    );

    if($update_settings) {
      Setting::setValue(
        self::API_KEY_STATE_SETTING_NAME,
        $state
      );
    }

    return $state;
  }

  function checkPremiumKey($key) {
    $this->initApi($key);
    $result = $this->api->checkPremiumKey();
    return $this->processPremiumKeyCheckResult($result);
  }

  function processPremiumKeyCheckResult(array $result) {
    $state_map = array(
      200 => self::PREMIUM_KEY_VALID,
      401 => self::PREMIUM_KEY_INVALID,
      402 => self::PREMIUM_KEY_USED
    );

    $update_settings = false;

    if(!empty($result['code']) && isset($state_map[$result['code']])) {
      if($result['code'] == self::PREMIUM_KEY_VALID
        && !empty($result['data']['expire_at'])
      ) {
        $key_state = self::PREMIUM_KEY_EXPIRING;
      } else {
        $key_state = $state_map[$result['code']];
      }
      $update_settings = true;
    } else {
      $key_state = self::PREMIUM_KEY_CHECK_ERROR;
    }

    $state = array(
      'state' => $key_state,
      'data' => !empty($result['data']) ? $result['data'] : null,
      'code' => !empty($result['code']) ? $result['code'] : self::CHECK_ERROR_UNKNOWN
    );

    if($update_settings) {
      Setting::setValue(
        self::PREMIUM_KEY_STATE_SETTING_NAME,
        $state
      );
    }

    return $state;
  }

  function updateSubscriberCount($result) {
    if(!empty($result['state'])
      && ($result['state'] === self::MAILPOET_KEY_VALID
      || $result['state'] === self::MAILPOET_KEY_EXPIRING)
    ) {
      return $this->api->updateSubscriberCount(Subscriber::getTotalSubscribers());
    }
    return null;
  }

  static function invalidateKey() {
    Setting::setValue(
      self::API_KEY_STATE_SETTING_NAME,
      array('state' => self::MAILPOET_KEY_INVALID)
    );
  }

  function onSettingsSave($settings) {
    $api_key_set = !empty($settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key']);
    $premium_key_set = !empty($settings['premium']['premium_key']);
    if($api_key_set && self::isMPSendingServiceEnabled()) {
      $result = $this->checkKey($settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key']);
      $this->updateSubscriberCount($result);
    }
    if($premium_key_set) {
      $this->checkPremiumKey($settings['premium']['premium_key']);
    }
  }
}
