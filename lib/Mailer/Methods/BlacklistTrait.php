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

  function getSubscriberEmailForBlacklistCheck($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriber_data);
    if (!isset($subscriber_data['email'])) {
      return $subscriber;
    }
    return $subscriber_data['email'];
  }

  function getBlacklist() {
    if (!$this->blacklist instanceof Blacklist) {
      $this->blacklist = new Blacklist();
    }
    return $this->blacklist;
  }

  function setBlacklist(Blacklist $blacklist) {
    $this->blacklist = $blacklist;
  }
}
