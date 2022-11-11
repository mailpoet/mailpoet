<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class CreateEmailAutomationAndWalkThroughCest {
  public function _before() {
    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
  }

  public function createEmailAutomationAndReceiveAnAutomatedEmail(\AcceptanceTester $i) {
    $i->wantTo('Create a automation to send an email after a user subscribed');
    $i->login();

    $i->amOnMailpoetPage('Automation');
    $i->see('Automations');
    $i->waitForText('Better engagement begins with automation');
    $i->dontSee('Active');
    $i->dontSee('Entered');

    $i->click('Start with a template');
    $i->see('Choose your automation template');
    $i->click('Welcome new subscribers');

    $i->waitForText('Draft');
    $i->click('Trigger');
    $i->fillField('When someone subscribes to the following lists:', 'Newsletter mailing list');
    $i->click('Delay');
    $i->fillField('Wait for', '5');

    $i->click('Send email');
    $i->fillField('"From" name','From');
    $i->fillField('"From" email address','test@mailpoet.com');
    $i->fillField('Subject','Automation-Test-Subject');

    $i->click('Design email');
    $i->waitForText('Newsletters');
    $i->click('Newsletters');
    $i->click('button[data-automation-id="select_template_0"]');
    $i->waitForText('Design');
    $i->click('Save and continue');

    $i->waitForText('Draft');

    $i->click('Send email');
    $i->click('Reply to');
    $i->waitForText('Use different email address for getting replies to the email');
    $i->click("//label[contains(text(), 'Use different email address for getting replies to the email')]");
    $i->fillField('"Reply to" name', 'Reply');
    $i->fillField('"Reply to" email address', 'reply@mailpoet.com');

    $i->click('Activate');
    $i->waitForText('Are you ready to activate?');

    // We use a selector to be specific about which Activate button we want to click.
    $panelActivateButton = '.mailpoet-automation-activate-panel__header-activate-button button';
    $i->click($panelActivateButton);

    // Check automation is activated
    $i->waitForText('"Welcome new subscribers" is now live.');
    $i->click('View all automations');
    $i->waitForText('Name');
    $i->see('Welcome new subscribers');
    $i->see('Active');
    $i->see('Entered 0'); //Actually I see "0 Entered", but this CSS switch is not caught by the test
    $i->dontSeeInDatabase('mp_actionscheduler_actions', ['hook' => 'mailpoet/automation/step']);

    $i->wantTo('Check a new subscriber gets the automation email.');
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/new');
    $i->fillField('#field_email', 'test@mailpoet.com');
    $i->fillField('#field_first_name', 'automation-tester-firstname');
    $i->selectOptionInSelect2('Newsletter mailing list');
    $i->click('Save');

    $i->amOnMailpoetPage('Automation');
    $i->seeInDatabase('mp_actionscheduler_actions', ['hook' => 'mailpoet/automation/step', 'status' => 'pending']);
    $i->waitForText('Welcome new subscribers');
    $i->see('Entered 1'); //Actually I see "0 Entered", but this CSS switch is not caught by the test
    $i->see('Processing 1');
    $i->see('Exited 0');
    $i->amOnMailboxAppPage();
    $i->see('Inbox (0)');

    // Jump the waiting time by scheduling the delay action to now.
    $i->triggerAutomationActionScheduler(); // Initialize the run, creates the delay step
    $i->triggerAutomationActionScheduler(); // Set delay scheduled at to now, runs delay and send email
    $i->triggerMailPoetActionScheduler(); // Runs the email queue

    $i->amOnUrl('http://test.local/wp-admin/');
    $i->amOnMailpoetPage('Automation');
    $i->waitForText('Welcome new subscribers');
    $i->see('Entered 1'); //Actually I see "0 Entered", but this CSS switch is not caught by the test
    $i->see('Processing 0');
    $i->see('Exited 1');
    $i->amOnMailboxAppPage();
    $i->see('Inbox (1)');
    $i->see('Automation-Test-Subject');
  }
}
