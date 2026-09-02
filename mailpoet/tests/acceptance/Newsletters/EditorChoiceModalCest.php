<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class EditorChoiceModalCest {

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
    $this->settings->withEditorChoiceModalEnabled();
  }

  public function createNewsletterUsingClassicEditor(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }
    $i->wantTo('Create a newsletter with the classic editor through the editor choice modal');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForText('Choose an email editor');
    // no editor is pre-selected on first open
    $i->seeElement('[data-automation-id="editor_choice_continue"][aria-disabled="true"]');
    $i->click('[data-automation-id="editor_choice_classic"]');
    $i->click('[data-automation-id="editor_choice_continue"]');
    $i->waitForElement('[data-automation-id="select_template_0"]');
  }

  public function createNewsletterUsingBlockEditor(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }
    $i->wantTo('Create a newsletter with the block editor through the editor choice modal');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForText('Choose an email editor');
    $i->click('[data-automation-id="editor_choice_block"]');
    $i->click('[data-automation-id="editor_choice_continue"]');
    // the block editor opens with the template selection modal
    $i->waitForElementClickable('.email-editor-start_from_scratch_button', 20);
  }

  public function cancelClosesTheModal(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }
    $i->wantTo('Close the editor choice modal without creating an email');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForText('Choose an email editor');
    $i->click('Cancel');
    $i->waitForElementNotVisible('.mailpoet-editor-choice-modal');
    $i->see('What would you like to create?');
  }
}
