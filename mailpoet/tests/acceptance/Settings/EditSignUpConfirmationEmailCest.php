<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Settings;

class EditSignUpConfirmationEmailCest {
  public function editSignUpConfContentAndVerify(\AcceptanceTester $i) {
    $i->wantTo('Edit sign up confirmation email');
    // make sure sign up confirmation is enabled
    $settings = new Settings();
    $settings->withSender('Confirmation Test From', 'from-confirmation-test@example.com');
    $settings->withConfirmationEmailEnabled();
    $settings->withConfirmationVisualEditorDisabled();
    $forms = new Form();
    $forms->withDefaultSuccessMessage();
    $confirmationEmailSubject = 'Confirmation email subject';

    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    // edit confirmation email
    $i->fillField('[data-automation-id="signup_confirmation_email_subject"]', $confirmationEmailSubject);
    $i->fillField('[data-automation-id="signup_confirmation_email_body"]', 'Confirmation email body [activation_link]link[/activation_link]');
    $i->click('[data-automation-id="settings-submit-button"]');
    // create form and subscribe
    $i->createFormAndSubscribe();
    // performing some clicks and getting back to verify the content
    // Explanation: in the past we had issue that the content was changed back to default
    // when we performed Verify button at Activation Tab and also performed some savings across the plugin.
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="activation_settings_tab"]');
    $i->click('Verify');
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('Emails');
    $i->amOnMailpoetPage('Settings');
    $i->waitForText('Settings');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->seeInField('[data-automation-id="signup_confirmation_email_subject"]', $confirmationEmailSubject);
    $i->seeInField('[data-automation-id="signup_confirmation_email_body"]', 'Confirmation email body [activation_link]link[/activation_link]');
    // check the received email
    $i->checkEmailWasReceived($confirmationEmailSubject);
    $i->click(Locator::contains('span.subject', $confirmationEmailSubject));
    $i->waitForText($confirmationEmailSubject);
    $i->waitForText('Confirmation Test From <from-confirmation-test@example.com>');
    $i->switchToIframe('#preview-html');
    $i->waitForText('Confirmation email body link');
  }
}
