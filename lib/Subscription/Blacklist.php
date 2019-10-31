<?php

namespace MailPoet\Subscription;

class Blacklist {
  const SALT = 'mailpoet';

  private $blacklistedEmails = [
    'e60c6e0e73997c92d4ceac78a6b6cbbe6249244c4106a3c31de421fc50370ecd' => 1,
  ];

  public function __construct(array $blacklistedEmails = null) {
    if ($blacklistedEmails) {
      $this->blacklistedEmails = array_fill_keys(array_map([$this, 'hash'], $blacklistedEmails), 1);
    }
  }

  public function isBlacklisted($email) {
    $hashed_email = $this->hash($email);
    return isset($this->blacklistedEmails[$hashed_email]);
  }

  private function hash($email) {
    return hash('sha256', $email . self::SALT);
  }
}
