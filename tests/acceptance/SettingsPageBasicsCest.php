<?php

namespace MailPoet\Test\Acceptance;

class SettingsPageBasicsCest {
  function allowSubscribeOnRegistrationPage(\AcceptanceTester $I) {
    $I->wantTo('Allow users to subscribe to lists on site registration page');
    $I->login();
    //Go to settings
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->checkOption('#settings[subscribe_on_register]');
    //work around funky legacy code with brittle xpath selectors
    $I->click(['xpath'=>'//*[@id="mailpoet_subscribe_in_form"]/p[3]/span/span[1]/span/ul/li/input']);
    $I->waitForText('My First List', 5);
    $I->fillField(['xpath'=>'//*[@id="mailpoet_subscribe_in_form"]/p[3]/span/span[1]/span/ul/li/input'], 'My First List');
    $I->pressKey(['xpath'=>'//*[@id="mailpoet_subscribe_in_form"]/p[3]/span/span[1]/span/ul/li/input'], \WebDriverKeys::ENTER);
    //save settings
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/p/input']);
    $I->logout();
    $I->amOnPage('/wp-login.php?action=register');
    $I->waitForElement(['css'=>'.registration-form-mailpoet'], 10);
    //log back in manually, login helper tries to load snapshot, doesn't work after logout in test above
    $I->amOnPage('/wp-login.php');
    $I->fillField(['name' => 'log'], 'admin');
    $I->fillField(['name' => 'pwd'], 'password');
    $I->click('Log In');
    $I->saveSessionSnapshot('login');
    $I->waitForText('MailPoet', 10);
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->uncheckOption('#settings[subscribe_on_register]');
    //save settings
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_form"]/p/input']);
  }
  function checkSettingsPagesLoad(\AcceptanceTester $I) {
    $I->wantTo('Confirm all settings pages load correctly');
    $I->login();
    $I->amOnMailPoetPage('Settings');
    //Basics Tab
    $I->waitForText('Basics', 10);
    //Sign-up Confirmation Tab
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[2]']);
    $I->waitForText('Enable sign-up confirmation', 10);
    //Send With Tab
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[3]']);
    $I->waitForText('MailPoet Sending Service', 10);
    //Advanced Tab
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[4]']);
    $I->waitForText('Bounce email address');
    //Activation Key Tab
    $I->click(['xpath'=>'//*[@id="mailpoet_settings_tabs"]/a[5]']);
    $I->waitForText('Activation Key', 10);
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