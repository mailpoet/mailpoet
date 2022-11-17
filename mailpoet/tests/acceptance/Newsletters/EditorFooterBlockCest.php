<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorFooterBlockCest {
  public function addFooter(\AcceptanceTester $i) {
    $i->wantTo('add Footer block to newsletter');
    $footerInEditor = ('[data-automation-id="footer"]');
    $footerSettingsIcon = ('[data-automation-id="settings_tool"]');
    $footerSettingsAssertion = ('[data-automation-id="footer_done_button"]');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithTextNoFooter.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_footer', '#mce_0');
    //Open settings by clicking on block
    $i->moveMouseOver($footerInEditor, 3, 2);
    $i->waitForText('Manage subscription');
    $i->click($footerSettingsIcon);
    $i->waitForElementVisible($footerSettingsAssertion);
    $i->click($footerSettingsAssertion);
  }
}
