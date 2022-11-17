<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Scenario;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

class UnauthorizedEmailNoticesCest {
  public function _before(\AcceptanceTester $i, Scenario $scenario) {
    if (!getenv('WP_TEST_MAILER_MAILPOET_API')) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }
  }

  public function authorizedEmailsValidation(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $settings->withSender('Unauthorized sender', 'unauthorized@email.com');

    (new Newsletter())->withSubject('Newsletter')
      ->withSenderAddress('unauthorized@email.com')
      ->create();

    (new Newsletter())->withSubject('Welcome email')
      ->withActiveStatus()
      ->withWelcomeTypeForSegment()
      ->withSenderAddress('unauthorized@email.com')
      ->create();

    $i->login();

    // save settings to trigger authorized email validation
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    // see both notices
    $i->amOnMailPoetPage('Newsletters');
    $i->waitForText('Sending all of your emails has been paused because your email address unauthorized@email.com hasn’t been authorized yet.');
    $i->waitForText('Your automatic emails have been paused because some email addresses haven’t been authorized yet.');

    // try button in the first notice, fill in invalid email
    $i->click('Use a different email address', '[data-notice="unauthorized-email-addresses-notice"]');
    $i->waitForText('It’s time to set your default FROM address!');
    $i->waitForText('Set one of your authorized email addresses as the default FROM email for your MailPoet emails.');
    $i->fillField(['id' => 'mailpoet-set-from-address-modal-input'], 'invalid@email.com');
    $i->click('Save', '.set-from-address-modal');
    $i->waitForText('Can’t use this email yet! Please authorize it first.');
    $i->click('.mailpoet-modal-close');

    // try button in the first notice, fill in authorized email
    $i->click('Use a different email address', '[data-notice="unauthorized-email-addresses-notice"]');
    $i->waitForText('It’s time to set your default FROM address!');
    $i->waitForText('Set one of your authorized email addresses as the default FROM email for your MailPoet emails.');
    $i->fillField(['id' => 'mailpoet-set-from-address-modal-input'], 'staff@mailpoet.com');
    $i->click('Save', '.set-from-address-modal');
    $i->waitForText('Excellent. Your authorized email was saved. You can change it in the Basics tab of the MailPoet settings.');

    $i->dontSee('Sending all of your emails has been paused because your email address unauthorized@email.com hasn’t been authorized yet.');
    $i->dontSee('Your automatic emails have been paused because some email addresses haven’t been authorized yet.');
  }
}
