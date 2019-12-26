<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorDividerBlockCest {
  public function addDivider(\AcceptanceTester $I) {
    $I->wantTo('add divider block to newsletter');
    $dividerSettings = ('[data-automation-id="settings_tool"]');
    $dividerResizeHandle = ('[data-automation-id="divider_resize_handle"]');
    $dividerInEditor = ('[data-automation-id="divider"]');
    $dividerSettingsAssertion = ('[data-automation-id="divider_selector"]');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->dragAndDrop('#automation_editor_block_divider', '#mce_0');
    $I->waitForElementNotVisible('.velocity-animating');
    //Open settings
    $I->moveMouseOver($dividerInEditor);
    $I->waitForElementVisible($dividerResizeHandle);
    $I->waitForElementVisible($dividerSettings);
    $I->click($dividerSettings);
    $I->waitForElementVisible($dividerSettingsAssertion);
    $I->click('Done');
  }

}
