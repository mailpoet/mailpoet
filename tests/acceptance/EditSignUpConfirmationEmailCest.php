<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;

class EditSignUpConfirmationEmailCest {

  function edit(\AcceptanceTester $I) {
    $I->wantTo('Edit sign up confirmation email');

    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');

    // make sure sign up confirmation is enabled
    $I->click('[data-automation-id="enable_signup_confirmation"]');
    $I->acceptPopup();

    // edit confirmation email
    $I->fillField('[data-automation-id="signup_confirmation_email_from_name"]', 'Confirmation Test From');
    $I->fillField('[data-automation-id="signup_confirmation_email_from_email"]', 'from-confirmation-test@example.com');
    $I->fillField('[data-automation-id="signup_confirmation_email_subject"]', 'Confirmation email subject');
    $I->fillField('[data-automation-id="signup_confirmation_email_body"]', 'Confirmation email body [activation_link]link[/activation_link]');

    $I->click('[data-automation-id="settings-submit-button"]');

    $I->createFormAndSubscribe();

    // check the received email
    $I->amOnMailboxAppPage();
    $I->waitForText('Confirmation email subject');
    $I->click(Locator::contains('span.subject', 'Confirmation email subject'));

    $I->waitForText('Confirmation email subject');
    $I->waitForText('Confirmation Test From <from-confirmation-test@example.com>');
    $I->switchToIframe('preview-html');
    $I->waitForText('Confirmation email body link');
  }

  function _after(\AcceptanceTester $I) {
    $I->cli('widget reset sidebar-1 --allow-root');
    $I->amOnUrl(\AcceptanceTester::WP_URL);
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');
    $I->fillField('[data-automation-id="signup_confirmation_email_subject"]', sprintf('Confirm your subscription to %1$s', get_option('blogname')));
    $I->fillField('[data-automation-id="signup_confirmation_email_body"]', "Hello,\n\nWelcome to our newsletter!\n\nPlease confirm your subscription to the list(s): [lists_to_confirm] by clicking the link below: \n\n[activation_link]Click here to confirm your subscription.[/activation_link]\n\nThank you,\n\nThe Team");
    $I->click('[data-automation-id="settings-submit-button"]');
  }
}
