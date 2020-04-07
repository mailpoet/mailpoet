<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Settings;

class SettingsPageBasicsCest {
  public function checkSettingsPagesLoad(\AcceptanceTester $i) {
    $i->wantTo('Confirm all settings pages load correctly');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    //Basics Tab
    $i->waitForText('Basics');
    $i->seeNoJSErrors();
    //Sign-up Confirmation Tab
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->seeNoJSErrors();
    //Send With Tab
    $i->click('[data-automation-id="send_with_settings_tab"]');
    $i->waitForText('MailPoet Sending Service');
    $i->seeNoJSErrors();
    //Advanced Tab
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForText('Newsletter task scheduler');
    $i->seeNoJSErrors();
    //Activation Key Tab
    $i->click('[data-automation-id="activation_settings_tab"]');
    $i->waitForText('Activation Key');
    $i->seeNoJSErrors();
  }

  public function editDefaultSenderInformation(\AcceptanceTester $i) {
    $i->wantTo('Confirm default sender information can be edited');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->fillField('[data-automation-id="from-name-field"]', 'Sender');
    $i->fillField('[data-automation-id="from-email-field"]', 'sender@fake.fake');
    $i->fillField('[data-automation-id="reply_to-name-field"]', 'Reply Name');
    $i->fillField('[data-automation-id="reply_to-email-field"]', 'reply@fake.fake');
    //save settings
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
  }

  public function allowSubscribeInComments(\AcceptanceTester $i) {
    $i->wantTo('Allow users to subscribe to lists in site comments');
    $postTitle = 'Hello world!';
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->checkOption('[data-automation-id="subscribe-on_comment-checkbox"]');
    $i->selectOptionInSelect2('My First List');
    //save settings
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->amOnPage('/');
    $i->waitForText($postTitle);
    $i->click($postTitle);
    $i->scrollTo('.comment-form-mailpoet');
    $i->waitForElement(['css' => '.comment-form-mailpoet']);
    //clear checkbox to hide Select2 from next test
    $i->amOnMailPoetPage('Settings');
    $i->uncheckOption('[data-automation-id="subscribe-on_comment-checkbox"]');
    //save settings
    $i->click('[data-automation-id="settings-submit-button"]');
    //check to make sure comment subscription form is gone
    $i->amOnPage('/');
    $i->waitForText($postTitle);
    $i->click($postTitle);
    $i->dontSee("Yes, add me to your mailing list");
  }

  public function checkSenderFreemailWarning(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_SMTP);
    $settings->withTodayInstallationDate();
    $nameField = '[data-automation-id="from-name-field"]';
    $emailField = '[data-automation-id="from-email-field"]';
    $i->wantTo('Confirm default sender information can be edited');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->fillField($nameField, 'Sender');
    $i->fillField($emailField, 'sender@email.com');
    $i->seeElement('[data-acceptance-id="freemail-sender-warning-old-installation"]');
    $i->see('contact@' . \AcceptanceTester::WP_DOMAIN);
    $i->fillField($emailField, 'sender@fake.fake');
    $i->dontseeElement('[data-acceptance-id="freemail-sender-warning-old-installation"]');

    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $i->reloadPage();

    $i->fillField($emailField, 'sender2@email.com');
    $i->dontSeeElement('[data-acceptance-id="freemail-sender-warning-new-installation"]');
    $i->fillField($emailField, 'sender@fake.fake');
    $i->dontSeeElement('[data-acceptance-id="freemail-sender-warning-new-installation"]');
  }
}
