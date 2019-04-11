<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorHeaderBlockCest {
  function addHeader(\AcceptanceTester $I) {
    $I->wantTo('add header block to newsletter');
    $newsletterTitle = 'Header Block Newsletter';
    $headerInEditor = ('[data-automation-id="header"]');
    $headerSettingsIcon = ('[data-automation-id="settings_tool"]');
    $headerSettingsAssertion = ('[data-automation-id="header_done_button"]');
    (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithTextNoHeader.json')
      ->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    // Create header block
    $I->waitForText('Header');
    $I->wait(1); // just to be sure
    $I->dragAndDrop('#automation_editor_block_header', '#mce_0');
    //Open settings by clicking on block
    $I->moveMouseOver($headerInEditor, 3, 2);
    $I->waitForText('Display problems?');
    $I->click($headerSettingsIcon);
    $I->waitForElementVisible($headerSettingsAssertion);
    $I->click($headerSettingsAssertion);
  }

}
