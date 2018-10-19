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
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[2]']);
    $I->waitForText('Enable sign-up confirmation', 10);
    $I->seeNoJSErrors();
    //Send With Tab
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[3]']);
    $I->waitForText('MailPoet Sending Service', 10);
    $I->seeNoJSErrors();
    //Advanced Tab
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[4]']);
    $I->waitForText('Bounce email address');
    $I->seeNoJSErrors();
    //Activation Key Tab
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[5]']);
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
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/p/input']);
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
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/p/input']);
    $I->amOnPage('/');
    $I->waitForText($post_title, 10);
    $I->click($post_title);
    $I->waitForElement(['css'=>'.comment-form-mailpoet'], 10);
    //clear checkbox to hide Select2 from next test
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->uncheckOption('#settings[subscribe_on_comment]');
    //save settings
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/p/input']);
  }
}