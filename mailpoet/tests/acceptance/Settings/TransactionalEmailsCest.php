<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class TransactionalEmailsCest {

  public function sendTransactionalEmailFallback(\AcceptanceTester $i) {
    $i->wantTo('Check that transactional email are sent even when MailPoet sending doesn‘t send');
    $settings = new Settings();
    $settings->withMisconfiguredSendingMethodSmtpMailhog();
    $settings->withTransactionEmailsViaMailPoet();
    $i->cli(['user', 'create', 'john_doe', 'john_doe@example.com', '--send-email']);
    $i->amOnMailboxAppPage();
    $i->checkEmailWasReceived('New User Registration');
    $i->checkEmailWasReceived('Login Details');
  }
}
