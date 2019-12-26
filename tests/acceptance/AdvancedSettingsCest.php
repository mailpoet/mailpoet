<?php

namespace MailPoet\Test\Acceptance;

class AdvancedSettingsCest {
  public function toggleAnonymousDataSetting(\AcceptanceTester $I) {
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

  public function addBounceEmailAddress(\AcceptanceTester $I) {
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

  public function toggleTaskScheduler(\AcceptanceTester $I) {
    $I->wantTo('Toggle the newsletter task schedule between cron options');
    $chooseWordPressCron = '[data-automation-id="wordress_cron_radio"]';
    $chooseMailPoetCron = '[data-automation-id="mailpoet_cron_radio"]';
    $chooseLinuxCron = '[data-automation-id="linux_cron_radio"]';
    $systemInfoWordPressCron = "Task Scheduler method: WordPress";
    $systemInfoMailPoetCron = "Task Scheduler method: MailPoet";
    $systemInfoLinuxCron = "Task Scheduler method: Linux Cron";
    $bounceAddressField = '[data-automation-id="bounce-address-field"]';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    //switch to MailPoet cron
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement($bounceAddressField);
    $I->click($chooseMailPoetCron);
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed
    $I->amOnMailPoetPage('Help');
    $I->waitForText('Knowledge Base');
    $I->click('System Info');
    $I->waitForText('The information below is useful');
    $I->waitForText($systemInfoMailPoetCron);
    //switch to linux cron
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement($bounceAddressField);
    $I->click($chooseLinuxCron);
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed
    $I->amOnMailPoetPage('Help');
    $I->waitForText('Knowledge Base');
    $I->click('System Info');
    $I->waitForText('The information below is useful');
    $I->waitForText($systemInfoLinuxCron);
    //switch to default cron
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement($bounceAddressField);
    $I->click($chooseWordPressCron);
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed
    $I->amOnMailPoetPage('Help');
    $I->waitForText('Knowledge Base');
    $I->click('System Info');
    $I->waitForText('The information below is useful');
    $I->waitForText($systemInfoWordPressCron);
  }

  public function toggleLogging(\AcceptanceTester $I) {
    $I->wantTo('Toggle logging options and confirm output');
    $loggingSelectBox = '[data-automation-id="logging-select-box"]';
    $chooseLogEverything = '[data-automation-id="log-everything"]';
    $chooseLogErrors = '[data-automation-id="log-errors"]';
    $chooseLogNothing = '[data-automation-id="log-nothing"]';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    //choose to log everything
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement($loggingSelectBox);
    $I->click($loggingSelectBox);
    $I->click($chooseLogEverything);
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->waitForElement($chooseLogEverything);
    //chose to log nothing
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->click($loggingSelectBox);
    $I->click($chooseLogNothing);
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->waitForElement($chooseLogNothing);
    //choose to log errors only, this is the default
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement($loggingSelectBox);
    $I->click($loggingSelectBox);
    $I->click($chooseLogErrors);
    $I->click($submitButton);
    $I->waitForText($successMessage);
    $I->waitForElement($chooseLogErrors);
  }

  public function checkInactiveSubscribers(\AcceptanceTester $I) {
    $I->wantTo('Check that inactive subsribers has default value');
    $inactiveSubscribersDefault = '[data-automation-id="inactive-subscribers-default"]';
    $I->login();
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="settings-advanced-tab"]');
    $I->waitForElement($inactiveSubscribersDefault);
    $I->seeCheckboxIsChecked($inactiveSubscribersDefault);

    $I->wantTo('See that inactive subsribers is disabled when tracking is disabled');
    $trackingDisabled = '[data-automation-id="tracking-disabled-radio"]';
    $inactiveSubscribersDisabled = '[data-automation-id="inactive-subscribers-disabled"]';
    $inactiveSubscribersEnabled = '[data-automation-id="inactive-subscribers-enabled"]';
    $I->click($trackingDisabled);
    $I->waitForElement($inactiveSubscribersDisabled);
    $I->dontSee($inactiveSubscribersEnabled);
  }

}
