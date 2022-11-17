<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorHeaderBlockCest {
  public function addHeader(\AcceptanceTester $i) {
    $i->wantTo('add header block to newsletter');
    $headerInEditor = ('[data-automation-id="header"]');
    $headerSettingsIcon = ('[data-automation-id="settings_tool"]');
    $headerSettingsAssertion = ('[data-automation-id="header_done_button"]');
    $footer = '[data-automation-id="footer"]';
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithTextNoHeader.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_header', '#mce_0');
    //Prevent flakyness by adding footer mouse over and some checks
    $i->moveMouseOver($footer, 3, 2);
    $i->moveMouseOver($headerInEditor, 3, 2);
    $i->waitForElement($headerInEditor);
    $i->waitForText('View this in your browser.');
    //Open settings by clicking on block
    $i->click($headerSettingsIcon);
    $i->wait(0.35); // CSS animation
    $i->scrollTo($headerSettingsAssertion);
    $i->waitForElementVisible($headerSettingsAssertion);
    $i->click($headerSettingsAssertion);
  }
}
