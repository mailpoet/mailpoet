<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods\Common;

use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Subscription\Blacklist;

class BlacklistCheckTest extends \MailPoetUnitTest {
  public function testItChecksTheRecipientAddress(): void {
    $blacklistCheck = new BlacklistCheck(new Blacklist(['blocked@example.com']));
    verify($blacklistCheck->isBlacklisted('blocked@example.com'))->true();
    verify($blacklistCheck->isBlacklisted('First Last <blocked@example.com>'))->true();
    verify($blacklistCheck->isBlacklisted('Jane <Doe> <blocked@example.com>'))->true();
  }
}
