<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorHeaderBlockCest {
  public function addHeader(\AcceptanceTester $i) {
    $i->wantTo('add header block to newsletter');
    $headerInEditor = ('[data-automation-id="header"]');
    $headerSettingsIcon = ('[data-automation-id="settings_tool"]');
    $headerSettingsAssertion = ('[data-automation-id="header_done_button"]');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithTextNoHeader.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->id);
    $i->dragAndDrop('#automation_editor_block_header', '#mce_0');
    //Open settings by clicking on block
    $i->moveMouseOver($headerInEditor, 3, 2);
    $i->waitForElement($headerInEditor);
    $i->waitForText('View this in your browser.');
    $i->click($headerSettingsIcon);
    $i->waitForElementVisible($headerSettingsAssertion);
    $i->click($headerSettingsAssertion);
  }
}
