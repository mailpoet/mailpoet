<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

/**
 * @group gutenberg-latest
 */
class AutomationEditorChoiceModalCest {
  public function _before(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');
    $settings->withEditorChoiceModalEnabled();
  }

  public function editContentOpensTheChooser(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }
    $i->wantTo('Choose the block editor from the automation send email step');
    $i->login();
    $this->openSendEmailStepWithoutEmail($i);

    $i->dontSeeElement('[data-automation-id="automation_send_email_editor_choice"]');
    $i->click('[data-automation-id="automation_send_email_design"]');
    $i->waitForText('Choose an email editor');
    $i->dontSeeCheckboxIsChecked('Remember my choice');
    $i->click('[data-automation-id="editor_choice_block"]');
    $i->click('[data-automation-id="editor_choice_continue"]');
    $i->waitForElement('[name="editor-canvas"]');
  }

  public function rememberedChoiceSkipsTheChooser(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }
    $i->wantTo('Remember the block editor and skip the chooser next time');
    $i->login();
    $this->openSendEmailStepWithoutEmail($i);

    $i->click('[data-automation-id="automation_send_email_design"]');
    $i->waitForText('Choose an email editor');
    $i->click('[data-automation-id="editor_choice_block"]');
    $i->checkOption('Remember my choice');
    $i->click('[data-automation-id="editor_choice_continue"]');

    $i->wantTo('Verify the pre-filled email is already saved and go back to the automation');
    $i->waitForElement('[name="editor-canvas"]');
    $i->waitForText('Saved', 20, '.edit-post-header');
    $i->click('[data-automation-id="email_editor_send_button"]');
    $i->waitForText('Inactive');

    $i->wantTo('Remove the email again and use the remembered editor');
    $i->click('Send email');
    $this->deleteAssignedEmail($i);
    $i->seeElement('[data-automation-id="automation_send_email_editor_choice"]');
    $i->click('[data-automation-id="automation_send_email_design"]');
    // The template content is used only for the first email, so the new one starts blank.
    $i->waitForText('Start with an email preset');
  }

  private function openSendEmailStepWithoutEmail(\AcceptanceTester $i): void {
    $i->amOnMailpoetPage('Automation');
    $i->waitForText('Better engagement begins with automation');
    $i->click('Start with a template');
    $i->click('Welcome new subscribers');
    $i->waitForElementVisible('.mailpoet-automation-editor-automation-flow');
    $i->click('Start building');
    $i->waitForText('Inactive');
    $i->click('Trigger');
    $i->fillField('When someone subscribes to the following lists:', 'Newsletter mailing list');
    $i->click('Send email');
    $i->fillField('"From" name', 'From Test');
    $i->fillField('"From" email address', 'test@mailpoet.com');
    $i->fillField('Subject', 'Automation-Editor-Choice-Subject');
  }

  private function deleteAssignedEmail(\AcceptanceTester $i): void {
    $i->waitForElementVisible('[aria-label="Delete email"]');
    $i->click('[aria-label="Delete email"]');
    $i->waitForText('This removes the email from the automation step.');
    $i->click('Delete email', '.components-modal__frame');
    $i->waitForElement('[data-automation-id="automation_send_email_design"]');
  }
}
