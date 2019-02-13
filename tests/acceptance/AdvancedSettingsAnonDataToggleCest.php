<?php

namespace MailPoet\Test\Acceptance;

class AdvancedSettingsAnonymousDataToggleCest {
  function toggleAnonDataSetting(\AcceptanceTester $I) {
    $I->wantTo('Confirm anonymous data settings can be toggled');
    $noAnonymousData = '[data-automation-id="analytics-no"]';
    $yesAnonymousData = '[data-automation-id="analytics-yes"]';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForText('Bounce email address');
    $I->selectOption($noAnonymousData, 'No');
    $I->click($submitButton);
    $I->waitForElementClickable($submitButton);
    $I->seeOptionIsSelected($noAnonymousData, 'No');
    $I->reloadPage();
    $I->selectOption($yesAnonymousData, 'Yes');
    $I->click($submitButton);
    $I->waitForElementClickable($submitButton);
    $I->seeOptionIsSelected($yesAnonymousData, 'Yes');
  }
}
