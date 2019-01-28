<?php

namespace MailPoet\Test\Acceptance;

class SettingsFreeEmailAsFromAddressTriggersAlertCest {
  function addFreeEmailAsFromAddress(\AcceptanceTester $I) {
    $I->wantTo('Confirm free emails as FROM address trigger alert message');
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
