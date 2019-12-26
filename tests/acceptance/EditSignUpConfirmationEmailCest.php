<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;

class EditSignUpConfirmationEmailCest {

  public function edit(\AcceptanceTester $I) {
    $I->wantTo('Edit sign up confirmation email');

    // make sure sign up confirmation is enabled
    $settings = new Settings();
    $settings->withSender('Confirmation Test From', 'from-confirmation-test@example.com');
    $settings->withConfirmationEmailEnabled();
    $forms = new Form();
    $forms->withDefaultSuccessMessage();

    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');

    // edit confirmation email
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
}
