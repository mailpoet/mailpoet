<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

class EmailTemplatesCest {
  public function selectEditSwapAndResetEmailTemplate(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }

    $i->wantTo('Create standard newsletter using Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard_email_dropdown"]');
    $i->waitForText('Create using the new email editor (Alpha)');
    $i->click('Create using the new email editor (Alpha)');
    $i->waitForText('Create modern, beautiful emails that embody your brand with advanced customization and editing capabilities.');
    $i->click('//button[text()="Continue"]');

    $this->selectTemplate($i, 'Newsletter - 1 Column');

    $i->wantTo('Verify correct template is selected');
    $i->waitForText('Newsletter', 10, '.mailpoet-email-sidebar__email-type-info');

    $i->wantTo('Edit template');
    $i->click('Newsletter', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Edit template');
    $i->click('Edit template');
    $i->waitForText('Note that the same template can be used by multiple emails, so any changes made here may affect other emails on the site.');
    $i->click('Continue');
    $i->waitForText('Newsletter', 10, '.mailpoet-email-sidebar__email-type-info');

    $textInTemplate = 'Text added to template';
    $i->wantTo('Add some text to the template');
    $i->waitForElement('[name="editor-canvas"]');
    $i->wait(1); // we need to wait for the iframe to initialize otherwise the switch does not work properly
    $i->switchToIFrame('[name="editor-canvas"]');
    $i->waitForElementVisible('.is-root-container', 20);
    $i->pressKey('[aria-label="Block: Paragraph"]', $textInTemplate);
    $i->switchToIFrame();

    $i->wantTo('Return to editor and save all');
    $i->click('Back', '.editor-document-bar');
    $i->waitForText('Save', 10, '.edit-post-header__settings');
    $i->click('Save', '.edit-post-header__settings');
    $i->waitForText('Save', 10, '.entities-saved-states__panel');
    $i->click('Save', '.entities-saved-states__panel');
    $i->waitForText('Send', 10, '.edit-post-header__settings');
    $this->checkTextIsInEmail($i, $textInTemplate);

    $i->wantTo('Swap template');
    $i->click('Newsletter', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Swap template');
    $i->click('Swap template');
    $this->selectTemplate($i, 'Newsletter'); // Todo - select different template when available
    $i->wantTo('Verify correct template is selected');
    $i->waitForText('Newsletter', 10, '.mailpoet-email-sidebar__email-type-info');

    $i->wantTo('Swap template back');
    $i->click('Newsletter', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Swap template');
    $i->click('Swap template');
    $this->selectTemplate($i, 'Newsletter');
    $this->checkTextIsInEmail($i, $textInTemplate);

    $i->click('Newsletter', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Edit template');
    $i->click('Edit template');
    $i->click('Continue');

    $i->wantTo('Restore template content to default');
    $i->click('[aria-label="Template actions"]');
    $i->waitForText('Reset');
    $i->click('Reset');
    $i->waitForText('This will clear ANY and ALL template customization. All updates made to the template will be lost. Do you want to proceed?');
    $i->click('Reset');
    $i->waitForText('"Newsletter" reset.');
    $this->checkTextIsNotInEmail($i, $textInTemplate);
  }

  private function selectTemplate(\AcceptanceTester $i, string $template): void {
    $i->wantTo("Select template $template");
    $i->waitForElementClickable('.email-editor-start_from_scratch_button');
    $i->click('[aria-label="Basic"]');
    $i->waitForElement('.block-editor-block-patterns-list__item-title');
    $i->waitForText($template, 5);
    $i->click("//h4[@class='block-editor-block-patterns-list__item-title' and text()='$template']");
  }

  private function checkTextIsInEmail(\AcceptanceTester $i, string $text): void {
    $i->switchToIFrame('[name="editor-canvas"]');
    $i->waitForElementVisible('.is-root-container');
    $i->see($text);
    $i->switchToIFrame();
  }

  private function checkTextIsNotInEmail(\AcceptanceTester $i, string $text): void {
    $i->switchToIFrame('[name="editor-canvas"]');
    $i->waitForElementVisible('.is-root-container');
    $i->dontSee($text);
    $i->switchToIFrame();
  }
}
