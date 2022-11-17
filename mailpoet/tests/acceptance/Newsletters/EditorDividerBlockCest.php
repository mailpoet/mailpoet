<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorDividerBlockCest {
  public function addDivider(\AcceptanceTester $i) {
    $i->wantTo('add divider block to newsletter');
    $dividerSettings = ('[data-automation-id="settings_tool"]');
    $dividerResizeHandle = ('[data-automation-id="divider_resize_handle"]');
    $dividerInEditor = ('[data-automation-id="divider"]');
    $dividerSettingsAssertion = ('[data-automation-id="divider_selector"]');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_divider', '#mce_0');
    $i->waitForElementNotVisible('.velocity-animating');
    //Open settings
    $i->moveMouseOver($dividerInEditor);
    $i->waitForElementVisible($dividerResizeHandle);
    $i->waitForElementVisible($dividerSettings);
    $i->click($dividerSettings);
    $i->waitForElementVisible($dividerSettingsAssertion);
    $i->click('Done');
  }
}
