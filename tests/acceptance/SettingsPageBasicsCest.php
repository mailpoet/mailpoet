<?php

 namespace MailPoet\Test\Acceptance;

class SettingsPageBasicsCest {
  function checkSettingsPagesLoad(\AcceptanceTester $I) {
    $I->wantTo('Confirm all settings pages load correctly');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    //Basics Tab
    $I->waitForText('Basics', 10);
    $I->seeNoJSErrors();
    //Sign-up Confirmation Tab
    $I->click('[data-automation-id="signup_settings_tab"]');
    $I->waitForText('Enable sign-up confirmation', 10);
    $I->seeNoJSErrors();
    //Send With Tab
    $I->click('[data-automation-id="send_with_settings_tab"]');
    $I->waitForText('MailPoet Sending Service', 10);
    $I->seeNoJSErrors();
    //Advanced Tab
    $I->click('[data-automation-id="settings_addvanced_tab"]');
    $I->waitForText('Bounce email address');
    $I->seeNoJSErrors();
    //Activation Key Tab
    $I->click('[data-automation-id="activation_settings_tab"]');
    $I->waitForText('Activation Key', 10);
    $I->seeNoJSErrors();
  }
  function editDefaultSenderInformation(\AcceptanceTester $I) {
    $I->wantTo('Confirm default sender information can be edited');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->fillField(['name' => 'sender[name]'], 'Sender');
    $I->fillField(['name' => 'sender[address]'], 'sender@fake.fake');
    $I->fillField(['name' => 'reply_to[name]'], 'Reply Name');
    $I->fillField(['name' => 'reply_to[address]'], 'reply@fake.fake');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved', 10);
  }
  function allowSubscribeInComments(\AcceptanceTester $I) {
    $I->wantTo('Allow users to subscribe to lists in site comments');
    $post_title = 'Hello world!';
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->checkOption('#settings[subscribe_on_comment]');
    $I->selectOptionInSelect2('My First List');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->amOnPage('/');
    $I->waitForText($post_title, 10);
    $I->click($post_title);
    $I->waitForElement(['css'=>'.comment-form-mailpoet'], 10);
    //clear checkbox to hide Select2 from next test
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->uncheckOption('#settings[subscribe_on_comment]');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    //check to make sure comment subscription form is gone
    $I->amOnPage('/');
    $I->waitForText($post_title, 10);
    $I->click($post_title);
    $I->doNotSee(['css'=>'.comment-form-mailpoet']);    
  }
}