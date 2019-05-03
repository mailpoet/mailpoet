<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorTextBlockCest {
  function addText(\AcceptanceTester $I) {
    $I->wantTo('add Text block to newsletter');
    $newsletterTitle = 'Text Block Newsletter';
    $textInEditor = ('[data-automation-id="text_block_in_editor"]');
    (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    // Create Text block
    $I->waitForText('Text');
    $I->waitForElementNotVisible('.velocity-animating');
    $I->dragAndDrop('#automation_editor_block_text', '#mce_1');
    $I->waitForText('Edit this to insert text.');
  }

}
