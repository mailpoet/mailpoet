<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Settings;

class SettingsFreeEmailAsFromAddressTriggersAlertCest {
  public function addFreeEmailAsFromAddressWithMSS(\AcceptanceTester $I) {
    $I->wantTo('Confirm free emails as FROM address does not trigger alert message when sending with MSS');
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $from_email_field = '[data-automation-id="settings-page-from-email-field"]';
    $from_name_field = '[data-automation-id="settings-page-from-name-field"]';
    $I->fillField($from_name_field, 'AlertUser');
    $I->fillField($from_email_field, 'alertuser@yahoo.com');
    $I->dontSeeElement('.sender_email_address_warning');
    $I->fillField($from_email_field, 'info@alertuser.com');
    $I->dontSeeElement('.sender_email_address_warning');
    $I->fillField($from_email_field, 'alertuser@hotmail.com');
    $I->dontSeeElement('.sender_email_address_warning');
  }

  public function addFreeEmailAsFromAddressWithoutMSS(\AcceptanceTester $I) {
    $I->wantTo('Confirm free emails as FROM address trigger alert message when sending without MSS');
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_PHPMAIL);
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $from_email_field = '[data-automation-id="settings-page-from-email-field"]';
    $from_name_field = '[data-automation-id="settings-page-from-name-field"]';
    $I->fillField($from_name_field, 'AlertUser');
    $I->fillField($from_email_field, 'alertuser@yahoo.com');
    $I->waitForElement('.sender_email_address_warning');
    $I->fillField($from_email_field, 'info@alertuser.com');
    $I->dontSeeElement('.sender_email_address_warning');
    $I->fillField($from_email_field, 'alertuser@hotmail.com');
    $I->waitForElement('.sender_email_address_warning');
  }
}
