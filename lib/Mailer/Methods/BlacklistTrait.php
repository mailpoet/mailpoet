<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Subscription\Blacklist;

trait BlacklistTrait {
  /** @var Blacklist */
  private $blacklist;

  function isBlacklisted($subscriber) {
    $email = $this->getSubscriberEmailForBlacklistCheck($subscriber);
    return $this->getBlacklist()->isBlacklisted($email);
  }

  private function getSubscriberEmailForBlacklistCheck($subscriber_string) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber_string, $subscriber_data);
    if (!isset($subscriber_data['email'])) {
      return $subscriber_string;
    }
    return $subscriber_data['email'];
  }

  private function getBlacklist() {
    if (!$this->blacklist instanceof Blacklist) {
      $this->blacklist = new Blacklist();
    }
    return $this->blacklist;
  }
}
