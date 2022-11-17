<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Scenario;
use MailPoet\Mailer\MailerLog;
use MailPoet\Test\DataFactories\Settings;

class AddSendingKeyCest {
  public function addMailPoetSendingKey(\AcceptanceTester $i, Scenario $scenario) {
    $i->wantTo('Add a MailPoet sending key');

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

    // validate key, activate MSS, install & activate Premium plugin
    $i->waitForText('Your key is valid');
    $i->waitForText('MailPoet Sending Service is active');
    $i->waitForText('It’s time to set your default FROM address!');
    $i->waitForText('Set one of your authorized email addresses as the default FROM email for your MailPoet emails.');
    $i->dontSee('A test email was sent to');

    // check the state after reload
    $i->reloadPage();
    $i->waitForText('Your key is valid');
    $i->waitForText('MailPoet Sending Service is active');
    $i->dontSee('A test email was sent to');

    // test modal for authorized FROM address
    $i->waitForText('Sending all of your emails has been paused because your email address wp@example.com hasn’t been authorized yet.');

    $i->click('Verify');
    $i->waitForText('It’s time to set your default FROM address!');
    $i->waitForText('Set one of your authorized email addresses as the default FROM email for your MailPoet emails.');

    $i->fillField(['id' => 'mailpoet-set-from-address-modal-input'], 'invalid@email.com');
    $i->click('Save', '.set-from-address-modal');
    $i->waitForText('Can’t use this email yet! Please authorize it first.');

    $i->fillField(['id' => 'mailpoet-set-from-address-modal-input'], 'blackhole@mailpoet.com');
    $i->click('Save', '.set-from-address-modal');
    $i->waitForText('Excellent. Your authorized email was saved. You can change it in the Basics tab of the MailPoet settings.');
    $i->dontSee('Sending all of your emails has been paused because your email address %s hasn’t been authorized yet.');
    $i->waitForText('A test email was sent to blackhole@mailpoet.com');

    // change MSS key state to pending approval, ensure pending approval notice is displayed
    $settings = new Settings();
    $settings->withMssKeyPendingApproval();
    $i->reloadPage();
    $i->waitForText('Note: your account is pending approval by MailPoet.');
    $i->waitForText('Rest assured, this only takes just a couple of hours. Until then, you can still send email previews to yourself. Any active automatic emails, like Welcome Emails, will be paused.');
    $i->dontSee('A test email was sent to');

    // try invalid key
    $i->fillField(['name' => 'premium[premium_key]'], 'invalid-key');
    $i->click('Verify');
    $i->waitForText('Your key is not valid for the MailPoet Sending Service');
    $i->waitForText('Your key is not valid for MailPoet Premium');
    $i->dontSee('Note: your account is pending approval by MailPoet.');
    $i->dontSee('Rest assured, this only takes just a couple of hours. Until then, you can still send email previews to yourself. Any active automatic emails, like Welcome Emails, will be paused.');
    $i->dontSee('A test email was sent to');
  }

  public function activateMss(\AcceptanceTester $i, Scenario $scenario) {
    $i->wantTo('Activate MSS');

    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!$mailPoetSendingKey) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }

    $settings = new Settings();
    $settings->withValidMssKey($mailPoetSendingKey);

    $keyActivationTab = '[data-automation-id="activation_settings_tab"]';
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click($keyActivationTab);

    // MSS not activated
    $i->waitForText('Your key is valid');
    $i->waitForText('MailPoet Sending Service is not active.');
    $i->waitForText('Activate MailPoet Sending Service');

    // activate MSS
    $i->click('Activate MailPoet Sending Service');
    $i->waitForText('MailPoet Sending Service is active');
    $i->dontSee('A test email was sent to');

    // test modal for authorized FROM address
    $i->waitForText('It’s time to set your default FROM address!');
    $i->waitForText('Set one of your authorized email addresses as the default FROM email for your MailPoet emails.');
    $i->fillField(['id' => 'mailpoet-set-from-address-modal-input'], 'blackhole@mailpoet.com');
    $i->click('Save', '.set-from-address-modal');
    $i->waitForText('A test email was sent to blackhole@mailpoet.com');
  }

  public function resumeSendingWhenKeyApproved(\AcceptanceTester $i, Scenario $scenario) {
    $i->wantTo('Resume sending when key approved on MSS activation');

    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!$mailPoetSendingKey) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }

    $settings = new Settings();
    $settings->withSendingMethodMailPoet();

    // MSS key pending approval, paused sending
    $settings = new Settings();
    $settings->withMssKeyPendingApproval();
    MailerLog::pauseSending(MailerLog::getMailerLog());

    // ensure status is paused
    $i->login();
    $i->amOnMailpoetPage('Help#/systemStatus');
    $i->waitForText('Status paused');

    // force key verification
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="activation_settings_tab"]');
    $i->waitForText('Your key is valid');
    $i->click('Verify');
    $i->waitForText('MailPoet Sending Service is active');

    // ensure status is running
    $i->amOnMailpoetPage('Help#/systemStatus');
    $i->scrollTo('.mailpoet-tab-content');
    $i->waitForText('Status running');
  }
}
