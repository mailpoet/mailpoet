<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorHeaderBlockCest {
  public function addHeader(\AcceptanceTester $I) {
    $I->wantTo('add header block to newsletter');
    $headerInEditor = ('[data-automation-id="header"]');
    $headerSettingsIcon = ('[data-automation-id="settings_tool"]');
    $headerSettingsAssertion = ('[data-automation-id="header_done_button"]');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithTextNoHeader.json')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->dragAndDrop('#automation_editor_block_header', '#mce_0');
    //Open settings by clicking on block
    $I->moveMouseOver($headerInEditor, 3, 2);
    $I->waitForText('View this in your browser.');
    $I->click($headerSettingsIcon);
    $I->waitForElementVisible($headerSettingsAssertion);
    $I->click($headerSettingsAssertion);
  }

}
