<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Mailer\Methods\Common;

use MailPoet\Subscription\Blacklist;

class BlacklistCheck {
  /** @var Blacklist */
  private $blacklist;

  public function __construct(
    ?Blacklist $blacklist = null
  ) {
    if (is_null($blacklist)) {
      $blacklist = new Blacklist();
    }
    $this->blacklist = $blacklist;
  }

  public function isBlacklisted($subscriber) {
    $email = $this->getSubscriberEmailForBlacklistCheck($subscriber);
    return $this->blacklist->isBlacklisted($email);
  }

  private function getSubscriberEmailForBlacklistCheck($subscriberString) {
    // Matches "Name <email>"
    preg_match('!^(?P<name>.*)\s<(?P<email>[^<>]+)>\z!s', $subscriberString, $subscriberData);
    if (!isset($subscriberData['email'])) {
      return $subscriberString;
    }
    return $subscriberData['email'];
  }
}
