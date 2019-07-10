<?php
namespace MailPoet\Subscription;

class Blacklist {
  const SALT = 'mailpoet';

  private $blacklist = [
    'e60c6e0e73997c92d4ceac78a6b6cbbe6249244c4106a3c31de421fc50370ecd' => 1,
  ];

  public function __construct(array $blacklist = null) {
    if ($blacklist) {
      $this->blacklist = array_fill_keys(array_map([$this, 'hash'], $blacklist), 1);
    }
  }

  public function isBlacklisted($email) {
    $hashed_email = $this->hash($email);
    return isset($this->blacklist[$hashed_email]);
  }

  private function hash($email) {
    return hash('sha256', $email . self::SALT);
  }
}
