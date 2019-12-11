<?php

namespace MailPoet\Util\License\Features;

use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;

class Subscribers {
  const SUBSCRIBERS_OLD_LIMIT = 2000;
  const SUBSCRIBERS_NEW_LIMIT = 1000;
  const NEW_LIMIT_DATE = '2019-11-00';
  const MSS_SUBSCRIBERS_LIMIT_SETTING_KEY = 'mta.mailpoet_api_key_state.data.site_active_subscriber_limit';
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
    $subscribers_count = $this->subscribers_repository->getTotalSubscribers();
    return $subscribers_count > $this->getSubscribersLimit();
  }

  public function hasAPIKey() {
    $has_mss_key = !empty($this->settings->get(Bridge::API_KEY_SETTING_NAME));
    $has_premium_key = !empty($this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME));
    return $has_mss_key || $has_premium_key;
  }

  public function getSubscribersLimit() {
    $has_mss_key = !empty($this->settings->get(Bridge::API_KEY_SETTING_NAME));
    $mss_subscribers_limit = $this->settings->get(self::MSS_SUBSCRIBERS_LIMIT_SETTING_KEY);
    if ($has_mss_key && !empty($mss_subscribers_limit)) return (int)$mss_subscribers_limit;

    $has_premium_key = !empty($this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME));
    $premium_subscribers_limit = $this->settings->get(self::PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY);
    if ($has_premium_key && !empty($premium_subscribers_limit)) return (int)$premium_subscribers_limit;

    $installation_time = strtotime($this->settings->get('installed_at'));
    $old_user = $installation_time < strtotime(self::NEW_LIMIT_DATE);
    return $old_user ? self::SUBSCRIBERS_OLD_LIMIT : self::SUBSCRIBERS_NEW_LIMIT;
  }
}
