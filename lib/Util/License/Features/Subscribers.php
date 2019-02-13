<?php
namespace MailPoet\Util\License\Features;

use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Util\License\License;

class Subscribers {
  public $license;
  const SUBSCRIBERS_LIMIT = 2000;

  function __construct($license = false) {
    $this->license = ($license) ? $license : License::getLicense();
  }

  function check($subscribers_limit = self::SUBSCRIBERS_LIMIT) {
    if ($this->license) return false;
    return SubscriberModel::getTotalSubscribers() > $subscribers_limit;
  }
}
