<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

/**
 * This test contains active AutomateWoo plugin
 * in order to potentially catch issue with
 * blank page when managing automation with
 * the plugin AutomateWoo active.
 */
class ConfirmLeaveWhenUnsavedChangesCest {
  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $i->activateAutomateWoo();
  }

  public function confirmationIsRequiredIfAutomationNotSaved(\AcceptanceTester $i) {
    $i->wantTo('Edit a new automation draft');

    $automationTitle = 'Welcome new subscribers';

    $i->login();

    $i->amOnMailpoetPage('Automation');
    $i->see('Automations');
    $i->waitForText('Better engagement begins with automation');
    $i->dontSee('Active');
    $i->dontSee('Entered');

    $i->click('Start with a template');
    $i->see('Start with a template', 'h1');
    $i->click($automationTitle);
    $i->click('Start building');

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
    $i->amOnMailpoetPage('Automation');
    $i->waitForText('Automations');
    $i->waitForText($automationTitle);
    $i->click($automationTitle);
    $i->waitForText('Draft');
    $i->waitForText('Move to Trash');
    $i->waitForText('Welcome email');
    $i->waitForText('Wait for 2 days');
  }
}
