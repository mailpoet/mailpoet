<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;

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
    $i->waitForElement($noAnonymousData);
    $i->click($noAnonymousData);
    //save + refresh
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->seeCheckboxIsChecked($noAnonymousData . ' input');
    //repeat for Yes
    $i->click($yesAnonymousData);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->seeCheckboxIsChecked($yesAnonymousData . ' input');
  }

  public function toggleTaskScheduler(\AcceptanceTester $i) {
    $i->wantTo('Toggle the newsletter task schedule between cron options');
    $chooseWordPressCron = '[data-automation-id="wordress_cron_radio"]';
    $chooseLinuxCron = '[data-automation-id="linux_cron_radio"]';
    $systemInfoWordPressCron = "Task Scheduler method: WordPress";
    $systemInfoLinuxCron = "Task Scheduler method: Linux Cron";
    $submitButton = '[data-automation-id="settings-submit-button"]';
    $successMessage = "Settings saved";
    $i->login();
    //switch to linux cron
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($chooseLinuxCron);
    $i->click($chooseLinuxCron);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($chooseLinuxCron);
    //check System Info to make sure the value changed
    $i->amOnMailPoetPage('Help');
    $i->waitForText('Knowledge Base');
    $i->click('System Info');
    $i->waitForText('The information below is useful');
    $i->waitForText($systemInfoLinuxCron);
    //switch to default cron
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($chooseWordPressCron);
    $i->click($chooseWordPressCron);
    $i->click($submitButton);
    $i->waitForText($successMessage);
    $i->waitForElement($chooseWordPressCron);
    //check System Info to make sure the value changed
    $i->amOnMailPoetPage('Help');
    $i->waitForText('Knowledge Base');
    $i->click('System Info');
    $i->waitForText('The information below is useful');
    $i->waitForText($systemInfoWordPressCron);
  }

  public function checkMembersPlugin(\AcceptanceTester $i) {
    $i->wantTo('Install Members plugin and confirm output');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    //check if there's proper text & link present without Members plugin
    $i->see('Members', Locator::href('https://wordpress.org/plugins/members/'));
    //install the Members plugin by MemberPress
    $i->cli(['plugin', 'install', 'members', '--activate']);
    //check if there's proper text & link present with Members plugin
    $i->reloadPage();
    $i->see('Manage using the Members plugin', Locator::href('?page=roles'));
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

  public function checkInactiveSubscribersAndEmails(\AcceptanceTester $i) {
    $i->wantTo('Check that inactive subscribers has default value');
    $inactiveSubscribersDefault = '[data-automation-id="inactive-subscribers-default"]';
    $trackingEnabled = '[data-automation-id="tracking-partial-radio"]';
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement($trackingEnabled);
    $i->click($trackingEnabled);
    $i->waitForElement($inactiveSubscribersDefault);
    $i->seeCheckboxIsChecked($inactiveSubscribersDefault . ' input');

    $i->wantTo('See that inactive subscribers and re-engagement emails are disabled when tracking is disabled');
    $trackingDisabled = '[data-automation-id="tracking-basic-radio"]';
    $inactiveSubscribersDisabled = '[data-automation-id="inactive-subscribers-disabled"]';
    $inactiveSubscribersEnabled = '[data-automation-id="inactive-subscribers-enabled"]';
    $i->click($trackingDisabled);
    $i->waitForElement($inactiveSubscribersDisabled);
    $i->dontSee($inactiveSubscribersEnabled);
  }
}
