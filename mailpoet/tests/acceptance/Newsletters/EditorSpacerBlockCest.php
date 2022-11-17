<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorSpacerBlockCest {
  public function addSpacer(\AcceptanceTester $i) {
    $i->wantTo('add spacer block to newsletter');
    $spacerResizeHandle = '[data-automation-id="spacer_resize_handle"]';
    $spacerInEditor = '[data-automation-id="spacer"]';
    $spacerSettingsAssertion = '[data-automation-id="spacer_done_button"]';
    $footer = '[data-automation-id="footer"]';
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_spacer', '#mce_1');
    //Open settings by clicking on block
    $i->moveMouseOver($footer, 3, 2);
    $i->moveMouseOver($spacerInEditor, 3, 2);
    $i->waitForText('View this in your browser.');
    $i->waitForElementVisible($spacerResizeHandle);
    $i->click($spacerInEditor);
    $i->waitForElementVisible($spacerSettingsAssertion);
    $i->waitForElement($spacerInEditor);
    $i->click($spacerSettingsAssertion);
  }
}
