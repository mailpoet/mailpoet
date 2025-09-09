<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class CreateAutomationEmailWithBlockEditorCest {
  public function _before(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');
  }

  public function createAutomationEmailWithBlockEditor(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }

    $i->wantTo('Create an automation email using the block email editor');
    $i->login();

    $i->wantTo('Enable block email editor for automation newsletters in settings');
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForText('Use block email editor for automation emails');
    $i->click('[data-automation-id="block-editor-for-automation-enabled"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    $i->wantTo('Create a new automation');
    $i->amOnMailpoetPage('Automation');
    $i->see('Automations');
    $i->waitForText('Better engagement begins with automation');

    $i->click('Start with a template');
    $i->see('Start with a template', 'h1');
    $i->click('Welcome new subscribers');
    $i->waitForElementVisible('.mailpoet-automation-editor-automation-flow');
    $i->click('Start building');

    $i->waitForText('Draft');
    $i->click('Trigger');
    $i->fillField('When someone subscribes to the following lists:', 'Newsletter mailing list');

    $i->wantTo('Configure the send email step to use block editor');
    $i->click('Send email');
    $i->fillField('"From" name', 'From Test');
    $i->fillField('"From" email address', 'test@mailpoet.com');
    $i->fillField('Subject', 'Automation-Block-Editor-Subject');

    $i->wantTo('Verify that clicking Design email redirects to block editor');
    $i->click('Design email');
    $i->waitForText('Start with an email preset');

    $this->closeTemplateSelectionModal($i);

    $i->wantTo('Verify we are in the block editor interface');
    $i->waitForElement('[name="editor-canvas"]');
    $i->wait(1); // we need to wait for the iframe to initialize otherwise the switch does not work properly
    $i->switchToIFrame('[name="editor-canvas"]');
    $i->waitForElementVisible('.is-root-container', 20);
    $i->waitForElementVisible('[aria-label="Block: Image"]');
    $i->waitForElementVisible('[aria-label="Block: Heading"]');

    $i->wantTo('Add content to the email using block editor');
    $i->click('[aria-label="Block: Paragraph"]');
    $i->type('This is automation email content created with block editor');
    $i->switchToIFrame();

    $i->wantTo('Confirm we do not see email subject and preheader fields');
    $i->click('Email', '.editor-sidebar__panel-tabs');
    $i->dontSeeElement('[data-automation-id="email_subject"]');
    $i->dontSeeElement('[data-automation-id="email_preheader"]');

    $i->wantTo('Save the email draft');
    $i->click('Save draft', '.edit-post-header');
    $i->waitForText('Saved');

    $i->wantTo('Return to automation editor and verify email is configured');
    $i->click('[aria-label="Close Settings"]', '.editor-sidebar__panel-tabs'); // Close the side panel as it obstructs the view of the save and continue button.
    $i->see('Save and continue');
    $i->click('[data-automation-id="email_editor_send_button"]'); // Save and continue button.
    $i->waitForText('Draft');

    $i->wantTo('Activate the automation');
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

    $i->wantTo('Test editing existing automation email with block editor');
    $i->click('Edit', '.mailpoet-automation-listing');
    $i->waitForText('Active');
    $i->click('Send email');
    $i->click('Edit content');

    $i->wantTo('Verify we return to block editor for editing');
    $i->waitForElement('[name="editor-canvas"]');
    $i->wait(1);
    $i->switchToIFrame('[name="editor-canvas"]');
    $i->see('This is automation email content created with block editor');
  }

  public function testAutomationEmailWithoutBlockEditorSetting(\AcceptanceTester $i) {
    $i->wantTo('Verify automation emails use legacy editor when block editor setting is disabled');
    $i->login();

    $i->wantTo('Ensure block email editor for automation newsletters is disabled');
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForText('Use block email editor for automation emails');
    $i->click('[data-automation-id="block-editor-for-automation-disabled"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    $i->wantTo('Create a new automation and verify it uses legacy editor');
    $i->amOnMailpoetPage('Automation');
    $i->click('Start with a template');
    $i->click('Welcome new subscribers');
    $i->waitForElementVisible('.mailpoet-automation-editor-automation-flow');
    $i->click('Start building');

    $i->waitForText('Draft');
    $i->click('Trigger');
    $i->fillField('When someone subscribes to the following lists:', 'Newsletter mailing list');

    $i->click('Send email');
    $i->fillField('"From" name', 'From Test');
    $i->fillField('"From" email address', 'test@mailpoet.com');
    $i->fillField('Subject', 'Legacy-Editor-Subject');

    $i->wantTo('Verify that clicking Design email redirects to legacy newsletter editor');
    $i->click('Design email');
    $i->waitForText('Newsletters');
    $i->click('Newsletters');
    $i->click('button[data-automation-id="select_template_0"]');
    $i->waitForText('Design');

    $i->wantTo('Verify we are in the legacy newsletter editor interface');
    $i->dontSeeElement('[name="editor-canvas"]'); // Block editor iframe should not be present
    $i->see('Design'); // Legacy editor header
  }

  private function closeTemplateSelectionModal(\AcceptanceTester $i): void {
    $i->wantTo('Close template selector');
    $i->waitForElementClickable('.email-editor-start_from_scratch_button');
    $i->click('[aria-label="Basic"]');
    $i->waitForElementVisible('.block-editor-block-preview__container');
    $i->click('[aria-label="Close"]');
  }
}
