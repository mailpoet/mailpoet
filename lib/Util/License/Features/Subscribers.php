<?php

namespace MailPoet\Util\License\Features;

use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;

class Subscribers {
  const SUBSCRIBERS_OLD_LIMIT = 2000;
  const SUBSCRIBERS_NEW_LIMIT = 1000;
  const NEW_LIMIT_DATE = '2019-11-00';

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
    $has_mss_key = !empty($this->settings->get(Bridge::API_KEY_SETTING_NAME));
    $has_premium_key = !empty($this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME));
    if ($has_mss_key || $has_premium_key) return false;
    return $subscribers_count > $this->getSubscribersLimit();
  }

  public function getSubscribersLimit() {
    $installation_time = strtotime($this->settings->get('installed_at'));
    $old_user = $installation_time < strtotime(self::NEW_LIMIT_DATE);
    return $old_user ? self::SUBSCRIBERS_OLD_LIMIT : self::SUBSCRIBERS_NEW_LIMIT;
  }
}
