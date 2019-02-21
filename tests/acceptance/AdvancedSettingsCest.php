<?php

namespace MailPoet\Test\Acceptance;

class AdvancedSettingsCest {
  function toggleAnonymousDataSetting(\AcceptanceTester $I) {
    $I->wantTo('Confirm anonymous data settings can be toggled on Advanced Settings Page');
    $noAnonymousData = '[data-automation-id="analytics-no"]';
    $yesAnonymousData = '[data-automation-id="analytics-yes"]';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForText('Bounce email address');
    $I->selectOption($noAnonymousData, 'No');
    //save + refresh
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->seeOptionIsSelected($noAnonymousData, 'No');
    //repeat for Yes
    $I->selectOption($yesAnonymousData, 'Yes');
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->seeOptionIsSelected($yesAnonymousData, 'Yes');
  }

  function addBounceEmailAddress(\AcceptanceTester $I) {
    $I->wantTo('Add a bounce email address on Advanced Settings page');
    $bounceAddressField = '[data-automation-id="bounce-address-field"]';
    $bounceAddressText = 'bounce@bounce.bounce';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement($bounceAddressField);
    $I->fillField($bounceAddressField, $bounceAddressText);
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed in db
    $I->amOnMailPoetPage('Help');
    $I->waitForText('Knowledge Base');
    $I->click('System Info');
    $I->waitForText('The information below is useful');
    $I->waitForText($bounceAddressText);
  }
  
}
