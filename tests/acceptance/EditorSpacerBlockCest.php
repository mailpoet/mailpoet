<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorSpacerBlockCest {
  function addSpacer(\AcceptanceTester $I) {
    $I->wantTo('add spacer block to newsletter');
    $newsletterTitle = 'Spacer Block Newsletter';
    $spacerResizeHandle = '[data-automation-id="spacer_resize_handle"]';
    $spacerInEditor = '[data-automation-id="spacer"]';
    $spacerSettingsAssertion = '[data-automation-id="spacer_done_button"]';
    (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    // Create divider block
    $I->waitForText('Spacer');
    $I->waitForElementNotVisible('.velocity-animating');
    $I->dragAndDrop('#automation_editor_block_spacer', '#mce_1');
    //Open settings by clicking on block
    $I->moveMouseOver($spacerInEditor, 3, 2);
    $I->waitForElementVisible($spacerResizeHandle);
    $I->click($spacerInEditor);
    $I->waitForElementVisible($spacerSettingsAssertion);
    $I->click($spacerSettingsAssertion);
  }

}
