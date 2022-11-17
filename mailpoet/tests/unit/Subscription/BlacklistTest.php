<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

class BlacklistTest extends \MailPoetUnitTest {
  public function testItChecksBlacklistedEmails() {
    $email = 'test@example.com';
    $domain = 'example.com';
    $blacklist = new Blacklist();
    $result = $blacklist->isBlacklisted($email);
    expect($result)->equals(false);
    $blacklist = new Blacklist([$email]);
    $result = $blacklist->isBlacklisted($email);
    expect($result)->equals(true);
    $blacklist = new Blacklist(null, [$domain]);
    $result = $blacklist->isBlacklisted($email);
    expect($result)->equals(true);
  }
}
