<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorSpacerBlockCest {
  public function addSpacer(\AcceptanceTester $I) {
    $I->wantTo('add spacer block to newsletter');
    $spacerResizeHandle = '[data-automation-id="spacer_resize_handle"]';
    $spacerInEditor = '[data-automation-id="spacer"]';
    $spacerSettingsAssertion = '[data-automation-id="spacer_done_button"]';
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->dragAndDrop('#automation_editor_block_spacer', '#mce_1');
    //Open settings by clicking on block
    $I->moveMouseOver($spacerInEditor, 3, 2);
    $I->waitForElementVisible($spacerResizeHandle);
    $I->click($spacerInEditor);
    $I->waitForElementVisible($spacerSettingsAssertion);
    $I->click($spacerSettingsAssertion);
  }

}
