<?php

namespace MailPoet\Test\Acceptance;

class SubscribeOnRegistrationPageCest {
  function allowSubscribeOnRegistrationPage(\AcceptanceTester $I) {
    $I->wantTo('Allow users to subscribe to lists on site registration page');
    $I->login();
    //Go to settings
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->checkOption('#settings[subscribe_on_register]');
    $I->selectOptionInSelect2("My First List");
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->logOut();
    $I->amOnPage('/wp-login.php?action=register');
    $I->waitForElement(['css'=>'.registration-form-mailpoet'], 10);
    //clear setting to hide select2 from subsequent tests
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->seeInCurrentUrl('page=mailpoet-settings');
    $I->uncheckOption('#settings[subscribe_on_register]');
    //save settings
    $I->click('[data-automation-id="settings-submit-button"]');
  }
}