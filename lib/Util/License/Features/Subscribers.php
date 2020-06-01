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
  const PREMIUM_SUPPORT_SETTING_KEY = 'premium.premium_key_state.data.support_tier';

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(SettingsController $settings, SubscribersRepository $subscribersRepository) {
    $this->settings = $settings;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function check() {
    $limit = $this->getSubscribersLimit();
    if ($limit === false) return false;
    $subscribersCount = $this->getSubscribersCount();
    return $subscribersCount > $limit;
  }

  public function getSubscribersCount() {
    if ($this->hasPremiumSupport()) {
      return $this->subscribersRepository->getTotalSubscribersWithoutWPUsers();
    }
    return $this->subscribersRepository->getTotalSubscribers();
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
    $state = $this->settings->get(self::MSS_KEY_STATE);
    return $state === Bridge::KEY_VALID || $state === Bridge::KEY_EXPIRING;
  }

  private function hasMssSubscribersLimit() {
    return !empty($this->settings->get(self::MSS_SUBSCRIBERS_LIMIT_SETTING_KEY));
  }

  private function getMssSubscribersLimit() {
    return (int)$this->settings->get(self::MSS_SUBSCRIBERS_LIMIT_SETTING_KEY);
  }

  private function hasValidPremiumKey() {
    $state = $this->settings->get(self::PREMIUM_KEY_STATE);
    return $state === Bridge::KEY_VALID || $state === Bridge::KEY_EXPIRING;
  }

  private function hasPremiumSubscribersLimit() {
    return !empty($this->settings->get(self::PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY));
  }

  private function getPremiumSubscribersLimit() {
    return (int)$this->settings->get(self::PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY);
  }

  public function hasPremiumSupport() {
    return $this->hasValidPremiumKey() && $this->settings->get(self::PREMIUM_SUPPORT_SETTING_KEY) === 'premium';
  }

  private function getFreeSubscribersLimit() {
    $installationTime = strtotime($this->settings->get('installed_at'));
    $oldUser = $installationTime < strtotime(self::NEW_LIMIT_DATE);
    return $oldUser ? self::SUBSCRIBERS_OLD_LIMIT : self::SUBSCRIBERS_NEW_LIMIT;
  }
}
