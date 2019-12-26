<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorFooterBlockCest {
  public function addFooter(\AcceptanceTester $I) {
    $I->wantTo('add Footer block to newsletter');
    $footerInEditor = ('[data-automation-id="footer"]');
    $footerSettingsIcon = ('[data-automation-id="settings_tool"]');
    $footerSettingsAssertion = ('[data-automation-id="footer_done_button"]');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithTextNoFooter.json')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->dragAndDrop('#automation_editor_block_footer', '#mce_0');
    //Open settings by clicking on block
    $I->moveMouseOver($footerInEditor, 3, 2);
    $I->waitForText('Manage subscription');
    $I->click($footerSettingsIcon);
    $I->waitForElementVisible($footerSettingsAssertion);
    $I->click($footerSettingsAssertion);
  }

}
