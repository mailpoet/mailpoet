<?php

namespace MailPoet\Services;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class Bridge {
  const API_KEY_SETTING_NAME = 'mta.mailpoet_api_key';
  const API_KEY_STATE_SETTING_NAME = 'mta.mailpoet_api_key_state';

  const AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING_NAME = 'authorized_emails_addresses_check';

  const PREMIUM_KEY_SETTING_NAME = 'premium.premium_key';
  const PREMIUM_KEY_STATE_SETTING_NAME = 'premium.premium_key_state';

  const PREMIUM_KEY_VALID = 'valid'; // for backwards compatibility until version 3.0.0
  const KEY_VALID = 'valid';
  const KEY_INVALID = 'invalid';
  const KEY_EXPIRING = 'expiring';
  const KEY_ALREADY_USED = 'already_used';

  const KEY_CHECK_ERROR = 'check_error';

  const CHECK_ERROR_UNAVAILABLE = 503;
  const CHECK_ERROR_UNKNOWN = 'unknown';

  const BRIDGE_URL = 'https://bridge.mailpoet.com';

  /** @var API|null */
  public $api;

  /** @var SettingsController */
  private $settings;

  public function __construct(SettingsController $settingsController = null) {
    if ($settingsController === null) {
      $settingsController = SettingsController::getInstance();
    }
    $this->settings = $settingsController;
  }

  /**
   * @deprecated Use non static function isMailpoetSendingServiceEnabled instead
   * @return bool
   */
  public static function isMPSendingServiceEnabled() {
    try {
      $mailerConfig = SettingsController::getInstance()->get(Mailer::MAILER_CONFIG_SETTING_NAME);
      return !empty($mailerConfig['method'])
        && $mailerConfig['method'] === Mailer::METHOD_MAILPOET;
    } catch (\Exception $e) {
      return false;
    }
  }

  public function isMailpoetSendingServiceEnabled() {
    try {
      $mailerConfig = SettingsController::getInstance()->get(Mailer::MAILER_CONFIG_SETTING_NAME);
      return !empty($mailerConfig['method'])
        && $mailerConfig['method'] === Mailer::METHOD_MAILPOET;
    } catch (\Exception $e) {
      return false;
    }
  }

  public static function isMSSKeySpecified() {
    $settings = SettingsController::getInstance();
    $key = $settings->get(self::API_KEY_SETTING_NAME);
    return !empty($key);
  }

  public static function isPremiumKeySpecified() {
    $settings = SettingsController::getInstance();
    $key = $settings->get(self::PREMIUM_KEY_SETTING_NAME);
    return !empty($key);
  }

  public static function pingBridge() {
    $params = [
      'blocking' => true,
      'timeout' => 10,
    ];
    $wp = new WPFunctions();
    $result = $wp->wpRemoteGet(self::BRIDGE_URL, $params);
    return $wp->wpRemoteRetrieveResponseCode($result) === 200;
  }

  public function initApi($apiKey) {
    if ($this->api) {
      $this->api->setKey($apiKey);
    } else {
      $this->api = new Bridge\API($apiKey);
    }
  }

  /**
   * @param string $key
   * @return API
   */
  public function getApi($key) {
    $this->initApi($key);
    assert($this->api instanceof API);
    return $this->api;
  }

  public function getAuthorizedEmailAddresses() {
    return $this
      ->getApi($this->settings->get(self::API_KEY_SETTING_NAME))
      ->getAuthorizedEmailAddresses();
  }

  public function checkMSSKey($apiKey) {
    $result = $this
      ->getApi($apiKey)
      ->checkMSSKey();
    return $this->processKeyCheckResult($result);
  }

  public function storeMSSKeyAndState($key, $state) {
    if (empty($state['state'])
      || $state['state'] === self::KEY_CHECK_ERROR
    ) {
      return false;
    }

    // store the key itself
    $this->settings->set(
      self::API_KEY_SETTING_NAME,
      $key
    );

    // store the key state
    $this->settings->set(
      self::API_KEY_STATE_SETTING_NAME,
      $state
    );
  }

  public function checkPremiumKey($key) {
    $result = $this
      ->getApi($key)
      ->checkPremiumKey();
    return $this->processKeyCheckResult($result);
  }

  private function processKeyCheckResult(array $result) {
    $stateMap = [
      200 => self::KEY_VALID,
      401 => self::KEY_INVALID,
      402 => self::KEY_ALREADY_USED,
      403 => self::KEY_INVALID,
    ];

    if (!empty($result['code']) && isset($stateMap[$result['code']])) {
      if ($stateMap[$result['code']] == self::KEY_VALID
        && !empty($result['data']['expire_at'])
      ) {
        $keyState = self::KEY_EXPIRING;
      } else {
        $keyState = $stateMap[$result['code']];
      }
    } else {
      $keyState = self::KEY_CHECK_ERROR;
    }

    return $this->buildKeyState(
      $keyState,
      $result
    );
  }

  public function storePremiumKeyAndState($key, $state) {
    if (empty($state['state'])
      || $state['state'] === self::KEY_CHECK_ERROR
    ) {
      return false;
    }

    // store the key itself
    $this->settings->set(
      self::PREMIUM_KEY_SETTING_NAME,
      $key
    );

    // store the key state
    $this->settings->set(
      self::PREMIUM_KEY_STATE_SETTING_NAME,
      $state
    );
  }

  private function buildKeyState($keyState, $result) {
    $state = [
      'state' => $keyState,
      'data' => !empty($result['data']) ? $result['data'] : null,
      'code' => !empty($result['code']) ? $result['code'] : self::CHECK_ERROR_UNKNOWN,
    ];

    return $state;
  }

  public function updateSubscriberCount($result) {
    if (
      (
        !empty($result['state'])
        && (
          $result['state'] === self::KEY_VALID
          || $result['state'] === self::KEY_EXPIRING
        )
      )
      && ($this->api instanceof API)
    ) {
      return $this->api->updateSubscriberCount(Subscriber::getTotalSubscribers());
    }
    return null;
  }

  public static function invalidateKey() {
    $settings = SettingsController::getInstance();
    $settings->set(
      self::API_KEY_STATE_SETTING_NAME,
      ['state' => self::KEY_INVALID]
    );
  }

  public function onSettingsSave($settings) {
    $apiKeySet = !empty($settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key']);
    $premiumKeySet = !empty($settings['premium']['premium_key']);
    if ($apiKeySet) {
      $apiKey = $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'];
      $state = $this->checkMSSKey($apiKey);
      $this->storeMSSKeyAndState($apiKey, $state);
      if (self::isMPSendingServiceEnabled()) {
        $this->updateSubscriberCount($state);
      }
    }
    if ($premiumKeySet) {
      $premiumKey = $settings['premium']['premium_key'];
      $state = $this->checkPremiumKey($premiumKey);
      $this->storePremiumKeyAndState($premiumKey, $state);
    }
  }
}
