<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorHistoryCest {

  const BUTTON_SELECTOR = '.mailpoet_button_block';

  function undoRedo(\AcceptanceTester $I) {
    $I->wantTo('Undo and redo');
    $newsletter = (new Newsletter())
        ->loadBodyFrom('newsletterWithText.json')
        ->withSubject('Undo redo test')
        ->create();

    $I->login();
    $I->amEditingNewsletter($newsletter->id);

    $I->dragAndDrop('#automation_editor_block_button', '#mce_0');
    $I->waitForElementVisible(self::BUTTON_SELECTOR);
    $I->waitForText('Autosaved');

    // Mouse undo
    $I->click('#mailpoet-history-arrow-undo');
    $I->waitForElementNotVisible(self::BUTTON_SELECTOR);

    // Mouse redo
    $I->click('#mailpoet-history-arrow-redo');
    $I->waitForElementVisible(self::BUTTON_SELECTOR);

    // Keyboard undo
    $I->pressKey('body', [\WebDriverKeys::CONTROL, 'z']);
    $I->waitForElementNotVisible(self::BUTTON_SELECTOR);

    // Keyboard redo
    $I->pressKey('body', [\WebDriverKeys::SHIFT, \WebDriverKeys::CONTROL, 'z']);
    $I->waitForElementVisible(self::BUTTON_SELECTOR);
  }

}
