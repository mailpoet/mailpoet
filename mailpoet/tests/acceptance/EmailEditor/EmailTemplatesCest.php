<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;

class EmailTemplatesCest {
  public function selectEditSwapAndResetEmailTemplate(\AcceptanceTester $i, $scenario) {
    if (!$this->checkRequiredWordpressVersion($i)) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below 6.4 and higher than 6.5');
    }
    (new Features())->withFeatureEnabled(FeaturesController::GUTENBERG_EMAIL_EDITOR);

    $i->wantTo('Create standard newsletter using Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard_email_dropdown"]');
    $i->waitForText('Create using new editor (Beta)');
    $i->click('Create using new editor (Beta)');
    $i->waitForText('Create modern, beautiful emails that embody your brand with advanced customization and editing capabilities.');
    $i->click('//button[text()="Continue"]');

    $this->selectTemplate($i, 'Simple Light');

    $i->wantTo('Verify correct template is selected');
    $i->waitForText('Simple Light', 10, '.mailpoet-email-sidebar__email-type-info');

    $i->wantTo('Edit template');
    $i->click('Simple Light', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Edit template');
    $i->click('Edit template');
    $i->waitForText('Note that the same template can be used by multiple emails, so any changes made here may affect other emails on the site.');
    $i->click('Continue');
    $i->waitForText('Simple Light', 10, '.mailpoet-email-sidebar__email-type-info');

    $textInTemplate = 'Text added to template';
    $i->wantTo('Add some text to the template');
    $i->waitForElement('[name="editor-canvas"]');
    $i->wait(1); // we need to wait for the iframe to initialize otherwise the switch does not work properly
    $i->switchToIFrame('[name="editor-canvas"]');
    $i->waitForElementVisible('.is-root-container', 20);
    $i->click('[aria-label="Block: Paragraph"]');
    $i->type($textInTemplate);
    $i->switchToIFrame();

    $i->wantTo('Return to editor and save all');
    $i->click('Back', '.editor-document-bar');
    $i->waitForText('Save email & template');
    $i->click('Save email & template');
    $i->waitForText('Save', 10, '.entities-saved-states__panel');
    $i->click('Save', '.entities-saved-states__panel');
    $i->waitForText('Saved');
    $this->checkTextIsInEmail($i, $textInTemplate);

    $i->wantTo('Swap template');
    $i->click('Simple Light', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Swap template');
    $i->click('Swap template');
    $this->selectTemplate($i, 'General Email');
    $i->wantTo('Verify correct template is selected');
    $i->waitForText('General Email', 10, '.mailpoet-email-sidebar__email-type-info');
    $this->checkTextIsNotInEmail($i, $textInTemplate);

    $i->wantTo('Swap template back');
    $i->click('General Email', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Swap template');
    $i->click('Swap template');
    $this->selectTemplate($i, 'Simple Light');
    $this->checkTextIsInEmail($i, $textInTemplate);

    $i->click('Simple Light', '.mailpoet-email-sidebar__email-type-info'); // Button in sidebar
    $i->waitForText('Edit template');
    $i->click('Edit template');
    $i->click('Continue');

    $i->wantTo('Restore template content to default');
    $i->click('[aria-label="Template actions"]');
    $i->waitForText('Reset');
    $i->click('Reset');
    $i->waitForText('Reset to default and clear all customization?');
    $i->click('Reset');
    $i->waitForText('"Simple Light" reset.');
    $this->checkTextIsNotInEmail($i, $textInTemplate);
  }

  private function checkRequiredWordpressVersion(\AcceptanceTester $i): bool {
    $wordPressVersion = $i->getWordPressVersion();
    // New email editor is not compatible with WP versions below 6.7
    if (version_compare($wordPressVersion, '6.7', '<')) {
      return false;
    }
    return true;
  }

  private function selectTemplate(\AcceptanceTester $i, string $template): void {
    $i->wantTo("Select template $template");
    $i->waitForElement('.block-editor-block-patterns-list__item-title');
    $i->waitForText($template);
    $i->click("//div[@class='block-editor-block-patterns-list__item-title' and text()='$template']");
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
