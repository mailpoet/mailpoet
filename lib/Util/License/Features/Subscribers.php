<?php

namespace MailPoet\Util\License\Features;

use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;

class Subscribers {
  const SUBSCRIBERS_OLD_LIMIT = 2000;
  const SUBSCRIBERS_NEW_LIMIT = 1000;
  const NEW_LIMIT_DATE = '2019-11-00';
  const MSS_KEY_STATE = 'mta.mailpoet_api_key_state.state';
  const MSS_SUBSCRIBERS_LIMIT_SETTING_KEY = 'mta.mailpoet_api_key_state.data.site_active_subscriber_limit';
  const PREMIUM_KEY_STATE = 'premium.premium_key_state.state';
  const PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY = 'premium.premium_key_state.data.site_active_subscriber_limit';

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribers_repository;

  public function __construct(SettingsController $settings, SubscribersRepository $subscribers_repository) {
    $this->settings = $settings;
    $this->subscribers_repository = $subscribers_repository;
  }

  public function check() {
    $limit = $this->getSubscribersLimit();
    if ($limit === false) return false;
    $subscribers_count = $this->subscribers_repository->getTotalSubscribers();
    return $subscribers_count > $limit;
  }

  public function hasValidApiKey() {
    return $this->hasValidMssKey() || $this->hasValidPremiumKey();
  }

  public function getSubscribersLimit() {
    if (!$this->hasValidApiKey()) {
      return $this->getFreeSubscribersLimit();
    }

    if ($this->hasValidMssKey() && $this->hasMssSubscribersLimit()) {
      return $this->getMssSubscribersLimit();
    }

    if ($this->hasValidPremiumKey() && $this->hasPremiumSubscribersLimit()) {
      return $this->getPremiumSubscribersLimit();
    }

    return false;
  }

  private function hasValidMssKey() {
    return $this->settings->get(self::MSS_KEY_STATE) === 'valid';
  }

  private function hasMssSubscribersLimit() {
    return !empty($this->settings->get(self::MSS_SUBSCRIBERS_LIMIT_SETTING_KEY));
  }

  private function getMssSubscribersLimit() {
    return (int)$this->settings->get(self::MSS_SUBSCRIBERS_LIMIT_SETTING_KEY);
  }

  private function hasValidPremiumKey() {
    return $this->settings->get(self::PREMIUM_KEY_STATE) === 'valid';
  }

  private function hasPremiumSubscribersLimit() {
    return !empty($this->settings->get(self::PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY));
  }

  private function getPremiumSubscribersLimit() {
    return (int)$this->settings->get(self::PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY);
  }

  private function getFreeSubscribersLimit() {
    $installation_time = strtotime($this->settings->get('installed_at'));
    $old_user = $installation_time < strtotime(self::NEW_LIMIT_DATE);
    return $old_user ? self::SUBSCRIBERS_OLD_LIMIT : self::SUBSCRIBERS_NEW_LIMIT;
  }
}
