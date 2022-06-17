<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class TransactionalEmailsCest {

  public function sendTransactionalEmailFallback(\AcceptanceTester $i) {
    $i->wantTo('Check that transactional email are sent even when MailPoet sending doesn‘t send');
    $settings = new Settings();
    $i->wantTo('Setup MailPoet to send transactional emails but having misconfigured SMTP settings.');
    $settings->withMisconfiguredSendingMethodSmtp();
    $settings->withTransactionEmailsViaMailPoet();
    $i->wantTo('Create a new WP user and make sure transactional email were received');
    $i->cli(['user', 'create', 'john_doe', 'john_doe@example.com', '--send-email']);
    $i->amOnMailboxAppPage();
    $i->checkEmailWasReceived('New User Registration');
    $i->checkEmailWasReceived('Login Details');
  }
}
