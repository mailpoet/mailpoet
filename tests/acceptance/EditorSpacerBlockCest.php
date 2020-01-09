<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorSpacerBlockCest {
  public function addSpacer(\AcceptanceTester $i) {
    $i->wantTo('add spacer block to newsletter');
    $spacerResizeHandle = '[data-automation-id="spacer_resize_handle"]';
    $spacerInEditor = '[data-automation-id="spacer"]';
    $spacerSettingsAssertion = '[data-automation-id="spacer_done_button"]';
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->id);
    $i->dragAndDrop('#automation_editor_block_spacer', '#mce_1');
    //Open settings by clicking on block
    $i->moveMouseOver($spacerInEditor, 3, 2);
    $i->waitForElementVisible($spacerResizeHandle);
    $i->click($spacerInEditor);
    $i->waitForElementVisible($spacerSettingsAssertion);
    $i->click($spacerSettingsAssertion);
  }

}
