<?php

namespace MailPoet\Test\Acceptance;

class AddSendingKeyCest {
  function addMailPoetSendingKey(\AcceptanceTester $I) { 
    $I->wantTo('Add a mailpoet sending key');
    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    $keyActivationTab = '[data-automation-id="activation_settings_tab"]';
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click($keyActivationTab);
    $I->fillField(['name' => 'premium[premium_key]'], $mailPoetSendingKey);
    $I->click('Verify');
    $I->waitForText('Your Premium key has been successfully validated.');
  }
}