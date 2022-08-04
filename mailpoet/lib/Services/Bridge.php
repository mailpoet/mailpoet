<?php

namespace MailPoet\Services;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
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

  /** @var SubscribersFeature */
  private $subscribersFeature;

  public function __construct(
    SettingsController $settingsController = null,
    SubscribersFeature $subscribersFeature = null
  ) {
    if ($settingsController === null) {
      $settingsController = SettingsController::getInstance();
    }
    if ($subscribersFeature === null) {
      $subscribersFeature = ContainerWrapper::getInstance()->get(SubscribersFeature::class);
    }
    $this->settings = $settingsController;
    $this->subscribersFeature = $subscribersFeature;
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

  public function getAuthorizedEmailAddresses($type = 'authorized'): array {
    $data = $this
      ->getApi($this->settings->get(self::API_KEY_SETTING_NAME))
      ->getAuthorizedEmailAddresses();
    if ($data && $type === 'all') {
      return $data;
    }
    return isset($data[$type]) ? $data[$type] : [];
  }

  /**
   * Create Authorized Email Address
   */
  public function createAuthorizedEmailAddress(string $emailAdress) {
    $data = $this
      ->getApi($this->settings->get(self::API_KEY_SETTING_NAME))
      ->createAuthorizedEmailAddress($emailAdress);

    return $data;
  }

  /**
   * Get a list of sender domains
   * returns an assoc array of [domainName => Array(DNS responses)]
   * pass in the domain arg to return only the DNS response for the domain
   * For format see @see https://github.com/mailpoet/services-bridge#sender-domains
   */
  public function getAuthorizedSenderDomains($domain = 'all'): array {
    $domain = strtolower($domain);

    $data = $this
      ->getApi($this->settings->get(self::API_KEY_SETTING_NAME))
      ->getAuthorizedSenderDomains();
    $data = $data ?? [];

    $allSenderDomains = [];

    foreach ($data as $subarray) {
      if (isset($subarray['domain'])) {
        $allSenderDomains[strtolower($subarray['domain'])] = $subarray['dns'] ?? [];
      }
    }

    if ($domain !== 'all') {
      // return an empty array if the provided domain can not be found
      return $allSenderDomains[$domain] ?? [];
    }

    return $allSenderDomains;
  }

  /**
   * Create a new Sender domain record
   * returns an Array of DNS response or array of error
   * @see https://github.com/mailpoet/services-bridge#verify-a-sender-domain for response format
   */
  public function createAuthorizedSenderDomain(string $domain): array {
    $data = $this
    ->getApi($this->settings->get(self::API_KEY_SETTING_NAME))
    ->createAuthorizedSenderDomain($domain);

    return $data['dns'] ?? $data;
  }

  /**
   * Verify Sender Domain records
   * returns an Array of DNS response or an array of error
   * @see https://github.com/mailpoet/services-bridge#verify-a-sender-domain
   */
  public function verifyAuthorizedSenderDomain(string $domain): array {
    $data = $this
    ->getApi($this->settings->get(self::API_KEY_SETTING_NAME))
    ->verifyAuthorizedSenderDomain($domain);

    return $data;
  }

  public function checkMSSKey($apiKey) {
    $result = $this
      ->getApi($apiKey)
      ->checkMSSKey();
    return $this->processKeyCheckResult($result);
  }

  public function storeMSSKeyAndState($key, $state) {
    if (
      empty($state['state'])
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
      if (
        $stateMap[$result['code']] == self::KEY_VALID
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
    if (
      empty($state['state'])
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

  public function updateSubscriberCount(string $key): bool {
    return $this->getApi($key)->updateSubscriberCount($this->subscribersFeature->getSubscribersCount());
  }

  public static function invalidateKey() {
    $settings = SettingsController::getInstance();
    $settings->set(
      self::API_KEY_STATE_SETTING_NAME,
      ['state' => self::KEY_INVALID]
    );
  }

  public function onSettingsSave($settings) {
    $apiKey = $settings[Mailer::MAILER_CONFIG_SETTING_NAME]['mailpoet_api_key'] ?? null;
    $premiumKey = $settings['premium']['premium_key'] ?? null;
    if (!empty($apiKey)) {
      $apiKeyState = $this->checkMSSKey($apiKey);
      $this->storeMSSKeyAndState($apiKey, $apiKeyState);
    }
    if (!empty($premiumKey)) {
      $premiumState = $this->checkPremiumKey($premiumKey);
      $this->storePremiumKeyAndState($premiumKey, $premiumState);
    }
    if ($apiKey && !empty($apiKeyState) && $apiKeyState['state'] === self::KEY_VALID) {
      return $this->updateSubscriberCount($apiKey);
    }
    if ($premiumKey && !empty($premiumState) && $premiumState['state'] === self::KEY_VALID) {
      return $this->updateSubscriberCount($apiKey);
    }
  }
}
