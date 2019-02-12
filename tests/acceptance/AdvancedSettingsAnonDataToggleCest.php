<?php

namespace MailPoet\Test\Acceptance;

class AdvancedSettingsAnonDataToggleCest {
  function toggleAnonDataSetting(\AcceptanceTester $I) {
    $I->wantTo('Confirm anon data settings can be toggled');
    $noAnonData = '[data-automation-id="analytics-no"]';
    $yesAnonData = '[data-automation-id="analytics-yes"]';
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForText('Bounce email address');
    $I->selectOption($noAnonData, 'No');
    $I->seeOptionIsSelected($noAnonData, 'No');
    $I->selectOption($yesAnonData, 'Yes');
    $I->seeOptionIsSelected($yesAnonData, 'Yes');
  }
}