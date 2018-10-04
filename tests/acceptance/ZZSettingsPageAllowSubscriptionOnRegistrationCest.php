<?php

namespace MailPoet\Test\Acceptance;

class ZZSettingsPageAllowSubscriptionOnRegistrationCest{
    function allowSubscribeOnRegistrationPage(\AcceptanceTester $I){
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
}