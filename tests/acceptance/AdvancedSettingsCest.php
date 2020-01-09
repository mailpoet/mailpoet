<?php

namespace MailPoet\Test\Acceptance;

class AdvancedSettingsCest {
  public function toggleAnonymousDataSetting(\AcceptanceTester $i) {
    $i->wantTo('Confirm anonymous data settings can be toggled on Advanced Settings Page');
    $noAnonymousData = '[data-automation-id="analytics-no"]';
    $yesAnonymousData = '[data-automation-id="analytics-yes"]';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForText('Bounce email address');
    $i->selectOption($noAnonymousData, 'No');
    //save + refresh
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->seeOptionIsSelected($noAnonymousData, 'No');
    //repeat for Yes
    $i->selectOption($yesAnonymousData, 'Yes');
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->seeOptionIsSelected($yesAnonymousData, 'Yes');
  }

  public function addBounceEmailAddress(\AcceptanceTester $i) {
    $i->wantTo('Add a bounce email address on Advanced Settings page');
    $bounceAddressField = '[data-automation-id="bounce-address-field"]';
    $bounceAddressText = 'bounce@bounce.bounce';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($bounceAddressField);
    $i->fillField($bounceAddressField, $bounceAddressText);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed in db
    $i->amOnMailPoetPage('Help');
    $i->waitForText('Knowledge Base');
    $i->click('System Info');
    $i->waitForText('The information below is useful');
    $i->waitForText($bounceAddressText);
  }

  public function toggleTaskScheduler(\AcceptanceTester $i) {
    $i->wantTo('Toggle the newsletter task schedule between cron options');
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
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($bounceAddressField);
    $i->click($chooseMailPoetCron);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed
    $i->amOnMailPoetPage('Help');
    $i->waitForText('Knowledge Base');
    $i->click('System Info');
    $i->waitForText('The information below is useful');
    $i->waitForText($systemInfoMailPoetCron);
    //switch to linux cron
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($bounceAddressField);
    $i->click($chooseLinuxCron);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed
    $i->amOnMailPoetPage('Help');
    $i->waitForText('Knowledge Base');
    $i->click('System Info');
    $i->waitForText('The information below is useful');
    $i->waitForText($systemInfoLinuxCron);
    //switch to default cron
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($bounceAddressField);
    $i->click($chooseWordPressCron);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($bounceAddressField);
    //check System Info to make sure the value changed
    $i->amOnMailPoetPage('Help');
    $i->waitForText('Knowledge Base');
    $i->click('System Info');
    $i->waitForText('The information below is useful');
    $i->waitForText($systemInfoWordPressCron);
  }

  public function toggleLogging(\AcceptanceTester $i) {
    $i->wantTo('Toggle logging options and confirm output');
    $loggingSelectBox = '[data-automation-id="logging-select-box"]';
    $chooseLogEverything = '[data-automation-id="log-everything"]';
    $chooseLogErrors = '[data-automation-id="log-errors"]';
    $chooseLogNothing = '[data-automation-id="log-nothing"]';
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    //choose to log everything
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($loggingSelectBox);
    $i->click($loggingSelectBox);
    $i->click($chooseLogEverything);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($chooseLogEverything);
    //chose to log nothing
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->click($loggingSelectBox);
    $i->click($chooseLogNothing);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($chooseLogNothing);
    //choose to log errors only, this is the default
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($loggingSelectBox);
    $i->click($loggingSelectBox);
    $i->click($chooseLogErrors);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($chooseLogErrors);
  }

  public function checkInactiveSubscribers(\AcceptanceTester $i) {
    $i->wantTo('Check that inactive subsribers has default value');
    $inactiveSubscribersDefault = '[data-automation-id="inactive-subscribers-default"]';
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($inactiveSubscribersDefault);
    $i->seeCheckboxIsChecked($inactiveSubscribersDefault);

    $i->wantTo('See that inactive subsribers is disabled when tracking is disabled');
    $trackingDisabled = '[data-automation-id="tracking-disabled-radio"]';
    $inactiveSubscribersDisabled = '[data-automation-id="inactive-subscribers-disabled"]';
    $inactiveSubscribersEnabled = '[data-automation-id="inactive-subscribers-enabled"]';
    $i->click($trackingDisabled);
    $i->waitForElement($inactiveSubscribersDisabled);
    $i->dontSee($inactiveSubscribersEnabled);
  }

}
