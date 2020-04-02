<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Scenario;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;
use MailPoetVendor\Carbon\Carbon;

class AuthorizedEmailAddressesValidationCest {
  public function _before(\AcceptanceTester $i, Scenario $scenario) {
    if (!getenv('WP_TEST_MAILER_MAILPOET_API')) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }
  }

  public function authorizedEmailsValidation(\AcceptanceTester $i) {
    $unauthorizedSendingEmail = 'unauthorized1@email.com';
    $errorMessagePrefix = 'Sending all of your emails has been paused because your email address ';
    $errorNoticeElement = '[data-notice="unauthorized-email-addresses-notice"]';
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $settings->withInstalledAt(new Carbon('2019-03-07'));
    $i->wantTo('Check that emails are validated on setting change');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->cantSee($errorMessagePrefix);

    // default sender is invalid
    $i->fillField('[data-automation-id="from-email-field"]', $unauthorizedSendingEmail);
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->reloadPage();
    $i->canSee($errorMessagePrefix, $errorNoticeElement);
    $i->canSee($unauthorizedSendingEmail, $errorNoticeElement);

    // Error message disappears after email is replaced with authorized email
    $i->fillField('[data-automation-id="from-email-field"]', \AcceptanceTester::AUTHORIZED_SENDING_EMAIL);
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->reloadPage();
    $i->cantSee($errorMessagePrefix);
  }

  public function authorizedEmailsInNewslettersValidation(\AcceptanceTester $i) {
    $subject = 'Subject Unauthorized Welcome Email';
    (new Newsletter())->withSubject($subject)
      ->withActiveStatus()
      ->withWelcomeTypeForSegment()
      ->withSenderAddress('unauthorized1@email.com')
      ->create();
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $settings->withInstalledAt(new Carbon('2019-03-07'));
    $i->wantTo('Check that emails are validated on setting change');
    $i->login();

    // Save settings to trigger initial validation
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    // Error notice is visible
    $i->amOnMailPoetPage('Emails');
    $updateLinkText = 'Update the from address of ' . $subject;
    $i->waitForText('Your automatic emails have been paused because some email addresses haven’t been authorized yet.');
    $i->waitForText($updateLinkText);

    // Setting the correct address will fix the error
    $i->click($updateLinkText);
    $i->switchToNextTab();
    $i->waitForElement('[name="sender_address"]');
    $i->fillField('[name="sender_address"]', \AcceptanceTester::AUTHORIZED_SENDING_EMAIL);
    $i->click('Activate');
    $i->waitForListingItemsToLoad();
    $i->cantSee('Your automatic emails have been paused because some email addresses haven’t been authorized yet.');
    $i->cantSee('Update the from address of Subject 1');
  }

  public function validationBeforeSendingNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Validate from address before sending newsletter');

    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $newsletter = (new Newsletter())
        ->loadBodyFrom('newsletterWithText.json')
        ->withSubject('Invalid from address')
        ->create();

    $i->login();
    $i->amEditingNewsletter($newsletter->id);
    $i->click('Next');
    $i->waitForText('Sender');
    $i->fillField('[name="sender_address"]', 'unauthorized@email.com');
    $i->selectOptionInSelect2('WordPress Users');
    $i->click('Send');
    $i->waitForElement('.parsley-invalidFromAddress');
  }
}
