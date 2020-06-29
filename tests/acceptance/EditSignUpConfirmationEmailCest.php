<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;

class EditSignUpConfirmationEmailCest {
  public function edit(\AcceptanceTester $i) {
    $i->wantTo('Edit sign up confirmation email');

    // make sure sign up confirmation is enabled
    $settings = new Settings();
    $settings->withSender('Confirmation Test From', 'from-confirmation-test@example.com');
    $settings->withConfirmationEmailEnabled();
    $forms = new Form();
    $forms->withDefaultSuccessMessage();

    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');

    // edit confirmation email
    $i->fillField('[data-automation-id="signup_confirmation_email_subject"]', 'Confirmation email subject');
    $i->fillField('[data-automation-id="signup_confirmation_email_body"]', 'Confirmation email body [activation_link]link[/activation_link]');

    $i->click('[data-automation-id="settings-submit-button"]');

    $i->createFormAndSubscribe();

    // check the received email
    $i->checkEmailWasReceived('Confirmation email subject');
    $i->click(Locator::contains('span.subject', 'Confirmation email subject'));

    $i->waitForText('Confirmation email subject');
    $i->waitForText('Confirmation Test From <from-confirmation-test@example.com>');
    $i->switchToIframe('preview-html');
    $i->waitForText('Confirmation email body link');
  }
}
