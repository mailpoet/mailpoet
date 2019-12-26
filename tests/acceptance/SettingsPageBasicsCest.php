<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Settings;

class SettingsPageBasicsCest {

  public function checkSettingsPagesLoad(\AcceptanceTester $I) {
    $I->wantTo('Confirm all settings pages load correctly');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    //Basics Tab
    $I->waitForText('Basics');
    $I->seeNoJSErrors();
    //Sign-up Confirmation Tab
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation');
    $I->seeNoJSErrors();
    //Send With Tab
    $I->click('[data-automation-id="send_with_settings_tab"]');
    $I->waitForText('MailPoet Sending Service');
    $I->seeNoJSErrors();
    //Advanced Tab
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForText('Bounce email address');
    $I->seeNoJSErrors();
    //Activation Key Tab
    $I->click('[data-automation-id="activation_settings_tab"]');
    $I->waitForText('Activation Key');
    $I->seeNoJSErrors();
  }

  public function editDefaultSenderInformation(\AcceptanceTester $I) {
    $I->wantTo('Confirm default sender information can be edited');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->fillField(['name' => 'sender[name]'], 'Sender');
    $I->fillField(['name' => 'sender[address]'], 'sender@fake.fake');
    $I->fillField(['name' => 'reply_to[name]'], 'Reply Name');
    $I->fillField(['name' => 'reply_to[address]'], 'reply@fake.fake');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');
  }

  public function allowSubscribeInComments(\AcceptanceTester $I) {
    $I->wantTo('Allow users to subscribe to lists in site comments');
    $post_title = 'Hello world!';
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->checkOption('#settings[subscribe_on_comment]');
    $I->selectOptionInSelect2('My First List');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->amOnPage('/');
    $I->waitForText($post_title);
    $I->click($post_title);
    $I->scrollTo('.comment-form-mailpoet');
    $I->waitForElement(['css' => '.comment-form-mailpoet']);
    //clear checkbox to hide Select2 from next test
    $I->amOnMailPoetPage('Settings');
    $I->uncheckOption('#settings[subscribe_on_comment]');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    //check to make sure comment subscription form is gone
    $I->amOnPage('/');
    $I->waitForText($post_title);
    $I->click($post_title);
    $I->dontSee("Yes, add me to your mailing list");
  }

  public function checkSenderFreemailWarning(\AcceptanceTester $I) {
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_SMTP);
    $settings->withTodayInstallationDate();

    $I->wantTo('Confirm default sender information can be edited');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->fillField(['name' => 'sender[name]'], 'Sender');
    $I->fillField(['name' => 'sender[address]'], 'sender@email.com');
    $I->seeElement('[data-acceptance-id="freemail-sender-warning-old-installation"]');
    $I->see('contact@' . \AcceptanceTester::WP_DOMAIN);
    $I->fillField(['name' => 'sender[address]'], 'sender@fake.fake');
    $I->dontseeElement('[data-acceptance-id="freemail-sender-warning-old-installation"]');

    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $I->reloadPage();

    $I->fillField(['name' => 'sender[address]'], 'sender2@email.com');
    $I->dontSeeElement('[data-acceptance-id="freemail-sender-warning-new-installation"]');
    $I->fillField(['name' => 'sender[address]'], 'sender@fake.fake');
    $I->dontSeeElement('[data-acceptance-id="freemail-sender-warning-new-installation"]');
  }
}
