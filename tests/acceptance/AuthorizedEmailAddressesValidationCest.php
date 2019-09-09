<?php

namespace MailPoet\Test\Acceptance;

use Carbon\Carbon;
use Codeception\Scenario;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

class AuthorizedEmailAddressesValidationCest {
  function _before(\AcceptanceTester $I, Scenario $scenario) {
    if (!getenv('WP_TEST_MAILER_MAILPOET_API')) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }
  }

  function authorizedEmailsValidation(\AcceptanceTester $I) {
    $unauthorized_sending_email = 'unauthorized1@email.com';
    $error_message_prefix = 'Sending all of your emails has been paused because your email address ';
    $error_notice_element = '[data-notice="unauthorized-email-addresses-notice"]';
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $settings->withInstalledAt(new Carbon('2019-03-07'));
    $I->wantTo('Check that emails are validated on setting change');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->cantSee($error_message_prefix);

    // default sender is invalid
    $I->fillField('[data-automation-id="settings-page-from-email-field"]', $unauthorized_sending_email);
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');
    $I->reloadPage();
    $I->canSee($error_message_prefix, $error_notice_element);
    $I->canSee($unauthorized_sending_email, $error_notice_element);

    // Error message disappears after email is replaced with authorized email
    $I->fillField('[data-automation-id="settings-page-from-email-field"]', \AcceptanceTester::AUTHORIZED_SENDING_EMAIL);
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');
    $I->reloadPage();
    $I->cantSee($error_message_prefix);
  }

  function authorizedEmailsInNewslettersValidation(\AcceptanceTester $I) {
    $subject = 'Subject Unauthorized Welcome Email';
    (new Newsletter())->withSubject($subject)
      ->withActiveStatus()
      ->withWelcomeTypeForSegment()
      ->withSenderAddress('unauthorized1@email.com')
      ->create();
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $settings->withInstalledAt(new Carbon('2019-03-07'));
    $I->wantTo('Check that emails are validated on setting change');
    $I->login();

    // Save settings to trigger initial validation
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');

    // Error notice is visible
    $I->amOnMailPoetPage('Emails');
    $update_link_text = 'Update the from address of ' . $subject;
    $I->waitForText('Your automatic emails have been paused, because some email addresses haven’t been authorized yet.');
    $I->waitForText($update_link_text);

    // Setting the correct address will fix the error
    $I->click($update_link_text);
    $I->switchToNextTab();
    $I->waitForElement('[name="sender_address"]');
    $I->fillField('[name="sender_address"]', \AcceptanceTester::AUTHORIZED_SENDING_EMAIL);
    $I->click('Activate');
    $I->waitForListingItemsToLoad();
    $I->cantSee('Your automatic emails have been paused, because some email addresses haven’t been authorized yet.');
    $I->cantSee('Update the from address of Subject 1');
  }

  function validationBeforeSendingNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Validate from address before sending newsletter');

    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $newsletter = (new Newsletter())
        ->loadBodyFrom('newsletterWithText.json')
        ->withSubject('Invalid from address')
        ->create();

    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');
    $I->waitForText('Sender');
    $I->fillField('[name="sender_address"]', 'unauthorized@email.com');
    $I->selectOptionInSelect2('WordPress Users');
    $I->click('Send');
    $I->waitForElement('.parsley-invalidFromAddress');
  }
}
