<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Settings;

class FreeEmailAsFromAddressTriggersAlertCest {
  public function addFreeEmailAsFromAddressWithMSS(\AcceptanceTester $i) {
    $i->wantTo('Confirm free emails as FROM address does not trigger alert message when sending with MSS');
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $fromEmailField = '[data-automation-id="from-email-field"]';
    $fromNameField = '[data-automation-id="from-name-field"]';
    $i->fillField($fromNameField, 'AlertUser');
    $i->fillField($fromEmailField, 'alertuser@yahoo.com');
    $i->dontSeeElement('.sender_email_address_warning');
    $i->fillField($fromEmailField, 'info@alertuser.com');
    $i->dontSeeElement('.sender_email_address_warning');
    $i->fillField($fromEmailField, 'alertuser@hotmail.com');
    $i->dontSeeElement('.sender_email_address_warning');
  }

  public function addFreeEmailAsFromAddressWithoutMSS(\AcceptanceTester $i) {
    $i->wantTo('Confirm free emails as FROM address trigger alert message when sending without MSS');
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_PHPMAIL);
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $fromEmailField = '[data-automation-id="from-email-field"]';
    $fromNameField = '[data-automation-id="from-name-field"]';
    $i->fillField($fromNameField, 'AlertUser');
    $i->fillField($fromEmailField, 'alertuser@yahoo.com');
    $i->waitForElement('.sender_email_address_warning');
    $i->fillField($fromEmailField, 'info@alertuser.com');
    $i->dontSeeElement('.sender_email_address_warning');
    $i->fillField($fromEmailField, 'alertuser@hotmail.com');
    $i->waitForElement('.sender_email_address_warning');
  }
}
