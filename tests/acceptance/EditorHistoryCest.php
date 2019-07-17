<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorHistoryCest {

  const BUTTON_SELECTOR = '.mailpoet_button_block';
  const REDO_SELECTOR = '#mailpoet-history-arrow-redo';
  const UNDO_SELECTOR = '#mailpoet-history-arrow-undo';
  const INACTIVE_SELECTOR = '.mailpoet_history_arrow_inactive';

  function undoRedo(\AcceptanceTester $I) {
    $I->wantTo('Undo and redo');
    $newsletter = (new Newsletter())
        ->loadBodyFrom('newsletterWithText.json')
        ->withSubject('Undo redo test')
        ->create();

    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $this->assessButtons($I, false, false);

    $I->dragAndDrop('#automation_editor_block_button', '#mce_0');
    $I->waitForElementVisible(self::BUTTON_SELECTOR);
    $I->waitForText('Autosaved');
    $this->assessButtons($I, true, false);

    // Mouse undo
    $I->click(self::UNDO_SELECTOR);
    $I->waitForElementNotVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($I, false, true);

    // Mouse redo
    $I->click(self::REDO_SELECTOR);
    $I->waitForElementVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($I, true, false);

    // Keyboard undo
    $I->pressKey('body', [\WebDriverKeys::CONTROL, 'z']);
    $I->waitForElementNotVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($I, false, true);

    // Keyboard redo
    $I->pressKey('body', [\WebDriverKeys::SHIFT, \WebDriverKeys::CONTROL, 'z']);
    $I->waitForElementVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($I, true, false);
  }

  private function assessButtons(\AcceptanceTester $I, $undo_clickable, $redo_clickable) {
    if ($undo_clickable) {
        $I->dontSeeElement(self::UNDO_SELECTOR . self::INACTIVE_SELECTOR);
    } else {
        $I->seeElement(self::UNDO_SELECTOR . self::INACTIVE_SELECTOR);
    }
    if ($redo_clickable) {
        $I->dontSeeElement(self::REDO_SELECTOR . self::INACTIVE_SELECTOR);
    } else {
        $I->seeElement(self::REDO_SELECTOR . self::INACTIVE_SELECTOR);
    }
  }

}
