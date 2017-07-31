<?php
namespace MailPoet\Services;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Bridge {
  const API_KEY_SETTING_NAME = 'mta.mailpoet_api_key';
  const API_KEY_STATE_SETTING_NAME = 'mta.mailpoet_api_key_state';

  const PREMIUM_KEY_SETTING_NAME = 'premium.premium_key';
  const PREMIUM_KEY_STATE_SETTING_NAME = 'premium.premium_key_state';

  const PREMIUM_KEY_VALID = 'valid';
  const PREMIUM_KEY_INVALID = 'invalid';
  const PREMIUM_KEY_EXPIRING = 'expiring';
  const PREMIUM_KEY_ALREADY_USED = 'already_used';

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

  static function isMSSKeySpecified() {
    $key = Setting::getValue(self::API_KEY_SETTING_NAME);
    return !empty($key);
  }

  static function isPremiumKeySpecified() {
    $key = Setting::getValue(self::PREMIUM_KEY_SETTING_NAME);
    return !empty($key);
  }

  function initApi($api_key) {
    if($this->api) {
      $this->api->setKey($api_key);
    } else {
      $this->api = new Bridge\API($api_key);
    }
  }

  function checkMSSKey($api_key) {
    $this->initApi($api_key);
    $result = $this->api->checkMSSKey();
    return $this->processMSSKeyCheckResult($result);
  }

  private function processMSSKeyCheckResult(array $result) {
    $state_map = array(
      200 => self::PREMIUM_KEY_VALID,
      401 => self::PREMIUM_KEY_INVALID,
      402 => self::PREMIUM_KEY_EXPIRING
    );

    if(!empty($result['code']) && isset($state_map[$result['code']])) {
      $key_state = $state_map[$result['code']];
    } else {
      $key_state = self::PREMIUM_KEY_CHECK_ERROR;
    }

    return $this->buildKeyState(
      $key_state,
      $result
    );
  }

  function storeMSSKeyAndState($key, $state) {
    if(empty($state['state'])
      || $state['state'] === self::PREMIUM_KEY_CHECK_ERROR
    ) {
      return false;
    }

    // store the key itself
    Setting::setValue(
      self::API_KEY_SETTING_NAME,
      $key
    );

    // store the key state
    Setting::setValue(
      self::API_KEY_STATE_SETTING_NAME,
      $state
    );
  }

  function checkPremiumKey($key) {
    $this->initApi($key);
    $result = $this->api->checkPremiumKey();
    return $this->processPremiumKeyCheckResult($result);
  }

  private function processPremiumKeyCheckResult(array $result) {
    $state_map = array(
      200 => self::PREMIUM_KEY_VALID,
      401 => self::PREMIUM_KEY_INVALID,
      402 => self::PREMIUM_KEY_ALREADY_USED
    );

    if(!empty($result['code']) && isset($state_map[$result['code']])) {
      if($state_map[$result['code']] == self::PREMIUM_KEY_VALID
        && !empty($result['data']['expire_at'])
      ) {
        $key_state = self::PREMIUM_KEY_EXPIRING;
      } else {
        $key_state = $state_map[$result['code']];
      }
    } else {
      $key_state = self::PREMIUM_KEY_CHECK_ERROR;
    }

    return $this->buildKeyState(
      $key_state,
      $result
    );
  }

  function storePremiumKeyAndState($key, $state) {
    if(empty($state['state'])
      || $state['state'] === self::PREMIUM_KEY_CHECK_ERROR
    ) {
      return false;
    }

    // store the key itself
    Setting::setValue(
      self::PREMIUM_KEY_SETTING_NAME,
      $key
    );

    // store the key state
    Setting::setValue(
      self::PREMIUM_KEY_STATE_SETTING_NAME,
      $state
    );
  }

  private function buildKeyState($key_state, $result) {
    $state = array(
      'state' => $key_state,
      'data' => !empty($result['data']) ? $result['data'] : null,
      'code' => !empty($result['code']) ? $result['code'] : self::CHECK_ERROR_UNKNOWN
    );

    return $state;
  }

  function updateSubscriberCount($result) {
    if(!empty($result['state'])
      && ($result['state'] === self::PREMIUM_KEY_VALID
      || $result['state'] === self::PREMIUM_KEY_EXPIRING)
    ) {
      return $this->api->updateSubscriberCount(Subscriber::getTotalSubscribers());
    }
    return null;
  }

  static function invalidateKey() {
    Setting::setValue(
      self::API_KEY_STATE_SETTING_NAME,
      array('state' => self::PREMIUM_KEY_INVALID)
    );
  }

  function onSettingsSave($settings) {
    $api_key_set = !empty($settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key']);
    $premium_key_set = !empty($settings['premium']['premium_key']);
    if($api_key_set) {
      $api_key = $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'];
      $state = $this->checkMSSKey($api_key);
      $this->storeMSSKeyAndState($api_key, $state);
      if(self::isMPSendingServiceEnabled()) {
        $this->updateSubscriberCount($state);
      }
    }
    if($premium_key_set) {
      $premium_key = $settings['premium']['premium_key'];
      $state = $this->checkPremiumKey($premium_key);
      $this->storePremiumKeyAndState($premium_key, $state);
    }
  }
}
