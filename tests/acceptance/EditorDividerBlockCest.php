<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorDividerBlockCest {
  function addDivider(\AcceptanceTester $I) {
    $I->wantTo('add divider block to newsletter');
    $newsletterTitle = 'Divider Block Newsletter';
    $dividerSettings = ('[data-automation-id="settings_tool"]');
    $dividerResizeHandle = ('[data-automation-id="divider_resize_handle"]');
    $dividerInEditor = ('[data-automation-id="divider"]');
    $dividerSettingsAssertion = ('[data-automation-id="divider_selector"]');
    (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    // Create divider block
    $I->waitForText('Divider');
    $I->wait(1); // just to be sure
    $I->dragAndDrop('#automation_editor_block_divider', '#mce_0');
    //Open settings
    $I->moveMouseOver($dividerInEditor);
    $I->waitForElementVisible($dividerResizeHandle);
    $I->waitForElementVisible($dividerSettings);
    $I->click($dividerSettings);
    $I->waitForElementVisible($dividerSettingsAssertion);
    $I->click('Done');
  }

}
