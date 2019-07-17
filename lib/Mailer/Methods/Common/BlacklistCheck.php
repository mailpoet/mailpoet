<?php
namespace MailPoet\Mailer\Methods\Common;

use MailPoet\Subscription\Blacklist;

class BlacklistCheck {
  /** @var Blacklist */
  private $blacklist;

  public function __construct(Blacklist $blacklist = null) {
    if (is_null($blacklist)) {
      $blacklist = new Blacklist();
    }
    $this->blacklist = $blacklist;
  }

  function isBlacklisted($subscriber) {
    $email = $this->getSubscriberEmailForBlacklistCheck($subscriber);
    return $this->blacklist->isBlacklisted($email);
  }

  private function getSubscriberEmailForBlacklistCheck($subscriber_string) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber_string, $subscriber_data);
    if (!isset($subscriber_data['email'])) {
      return $subscriber_string;
    }
    return $subscriber_data['email'];
  }
}
