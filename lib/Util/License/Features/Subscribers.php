<?php

namespace MailPoet\Util\License\Features;

use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\License;

class Subscribers {
  const SUBSCRIBERS_OLD_LIMIT = 2000;
  const SUBSCRIBERS_NEW_LIMIT = 1000;
  const NEW_LIMIT_DATE = '2019-11-00';

  private $license;

  /** @var int */
  private $installation_time;

  /** @var int */
  private $subscribers_count;

  function __construct(SettingsController $settings, $license = false) {
    $this->license = ($license) ? $license : License::getLicense();
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
