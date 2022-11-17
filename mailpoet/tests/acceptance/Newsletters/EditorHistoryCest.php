<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use MailPoet\Test\DataFactories\Newsletter;

class EditorHistoryCest {

  const BUTTON_SELECTOR = '.mailpoet_button_block';
  const REDO_SELECTOR = '#mailpoet-history-arrow-redo';
  const UNDO_SELECTOR = '#mailpoet-history-arrow-undo';
  const INACTIVE_SELECTOR = '.mailpoet_history_arrow_inactive';

  public function undoRedo(\AcceptanceTester $i) {
    $i->wantTo('Undo and redo');
    $newsletter = (new Newsletter())
        ->loadBodyFrom('newsletterWithText.json')
        ->withSubject('Undo redo test')
        ->create();

    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $this->assessButtons($i, false, false);

    $i->dragAndDrop('#automation_editor_block_button', '#mce_0');
    $i->waitForElementVisible(self::BUTTON_SELECTOR);
    $i->waitForText('Autosaved');
    $this->assessButtons($i, true, false);

    // Mouse undo
    $i->click(self::UNDO_SELECTOR);
    $i->waitForElementNotVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($i, false, true);

    // Mouse redo
    $i->click(self::REDO_SELECTOR);
    $i->waitForElementVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($i, true, false);

    // Keyboard undo
    $i->pressKey('body', [WebDriverKeys::CONTROL, 'z']);
    $i->waitForElementNotVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($i, false, true);

    // Keyboard redo
    $i->pressKey('body', [WebDriverKeys::SHIFT, WebDriverKeys::CONTROL, 'z']);
    $i->waitForElementVisible(self::BUTTON_SELECTOR);
    $this->assessButtons($i, true, false);
  }

  private function assessButtons(\AcceptanceTester $i, $undoClickable, $redoClickable) {
    if ($undoClickable) {
        $i->dontSeeElement(self::UNDO_SELECTOR . self::INACTIVE_SELECTOR);
    } else {
        $i->seeElement(self::UNDO_SELECTOR . self::INACTIVE_SELECTOR);
    }
    if ($redoClickable) {
        $i->dontSeeElement(self::REDO_SELECTOR . self::INACTIVE_SELECTOR);
    } else {
        $i->seeElement(self::REDO_SELECTOR . self::INACTIVE_SELECTOR);
    }
  }
}
