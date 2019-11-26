<?php

namespace MailPoet\Util\License\Features;

use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class Subscribers {
  const SUBSCRIBERS_OLD_LIMIT = 2000;
  const SUBSCRIBERS_NEW_LIMIT = 1000;
  const NEW_LIMIT_DATE = '2019-11-00';

  private $license;

  /** @var int */
  private $installation_time;

  /** @var int */
  private $subscribers_count;

  function __construct(SettingsController $settings) {
    $has_mss_key = !empty($settings->get(Bridge::API_KEY_SETTING_NAME));
    $has_premium_key = !empty($settings->get(Bridge::PREMIUM_KEY_SETTING_NAME));
    $this->license = $has_mss_key || $has_premium_key;
    $this->installation_time = strtotime($settings->get('installed_at'));
    $this->subscribers_count = SubscriberModel::getTotalSubscribers();
  }

  function check() {
    if ($this->license) return false;
    return $this->subscribers_count > $this->getSubscribersLimit();
  }

  function getSubscribersLimit() {
    $old_user = $this->installation_time < strtotime(self::NEW_LIMIT_DATE);
    return $old_user ? self::SUBSCRIBERS_OLD_LIMIT : self::SUBSCRIBERS_NEW_LIMIT;
  }
}
