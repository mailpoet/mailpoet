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
    $i->waitForText('Take a first look at our new email editor. It introduces a more flexible, modern way to design your emails.');
    $i->click('//button[text()="Try it now"]');

    $this->selectTemplate($i, 'Newsletter - 1 Column');

    $i->wantTo('Verify settings panel is visible');
    $i->waitForText('Settings', 10, '.woocommerce-email-editor__settings-panel');

    $i->wantTo('Edit template');
    $i->click('Settings', '.woocommerce-email-editor__settings-panel');
    $i->waitForText('Template');
    $i->click('Newsletter', '.woocommerce-email-editor__settings-panel');
    $i->waitForText('Edit template');
    $i->click('Edit template');
    $i->waitForText('This template is used by multiple emails. Any changes made would affect other emails on the site. Are you sure you want to edit the template?');
    $i->click('Edit template');

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
    $i->waitForText('Save draft', 10, '.edit-post-header');
    $i->click('Save draft', '.edit-post-header');
    $i->waitForText('Saved', 10, '.edit-post-header');
    $i->click('Save', '.editor-header__settings');
    $i->waitForText('Are you ready to save?', 10, '.entities-saved-states__panel');
    $i->click('Save', '.entities-saved-states__panel');
    $i->waitForText('Review & send', 10, '.editor-header__settings');
    $this->checkTextIsInEmail($i, $textInTemplate);

    $i->wantTo('Edit template');
    $i->click('Newsletter', '.woocommerce-email-editor__settings-panel');
    $i->waitForText('Edit template');
    $i->click('Edit template');
    $i->waitForText('This template is used by multiple emails. Any changes made would affect other emails on the site. Are you sure you want to edit the template?');
    $i->click('Edit template');

    $i->wantTo('Restore template content to default');
    $i->click('.editor-all-actions-button');
    $i->waitForElementVisible('//div[@role="menuitem"]//span[normalize-space(.)="Reset"]');
    $i->click('//div[@role="menuitem"][.//span[normalize-space(.)="Reset"]]');
    $i->waitForText('Reset to default and clear all customizations?', 10, '.components-modal__content');
    $i->click('.components-modal__content .components-button.is-primary');
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
