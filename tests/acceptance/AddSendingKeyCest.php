<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Scenario;

class AddSendingKeyCest {
  public function addMailPoetSendingKey(\AcceptanceTester $i, Scenario $scenario) {
    $i->wantTo('Add a mailpoet sending key');

    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!$mailPoetSendingKey) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }

    $keyActivationTab = '[data-automation-id="activation_settings_tab"]';
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click($keyActivationTab);
    $i->fillField(['name' => 'premium[premium_key]'], $mailPoetSendingKey);
    $i->click('Verify');
    $i->waitForText('Your Premium key has been successfully validated.');
  }
}
