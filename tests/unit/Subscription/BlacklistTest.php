<?php
namespace MailPoet\Subscription;

class BlacklistTest extends \MailPoetUnitTest {
  function testItChecksBlacklistedEmails() {
    $email = 'test@example.com';
    $blacklist = new Blacklist();
    $result = $blacklist->isBlacklisted($email);
    expect($result)->equals(false);
    $blacklist->addEmail($email);
    $result = $blacklist->isBlacklisted($email);
    expect($result)->equals(true);
  }
}
