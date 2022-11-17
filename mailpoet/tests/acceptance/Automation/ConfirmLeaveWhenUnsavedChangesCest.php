<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

class ConfirmLeaveWhenUnsavedChangesCest {
  public function confirmationIsRequiredIfAutomationNotSaved(\AcceptanceTester $i) {
    $i->wantTo('Edit a new automation draft');
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

    $i->wantTo('Leave the page without saving.');
    $i->reloadPage();
    $i->cancelPopup();

    $i->wantTo('Leave the page after saving.');
    $i->click('Save');
    $i->waitForText('saved');
    $i->reloadPage();
    $i->waitForText('Draft');
  }
}
