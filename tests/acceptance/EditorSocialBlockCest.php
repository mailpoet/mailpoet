<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorSocialBlockCest {
  public function addSocialBlock(\AcceptanceTester $i) {
    $i->wantTo('add social block to newsletter');
    $socialBlockInEditor = ('[data-automation-id="socialBlock"]');
    $socialBlockSettingsAssertion = ('[data-automation-id="social_select_another_network"]');
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->id);
    $i->dragAndDrop('#automation_editor_block_social', '#mce_1');
    //Open settings by clicking on block
    $i->moveMouseOver($socialBlockInEditor, 3, 2);
    $i->clickWithLeftButton(); // Clicks on the position where mouse cursor is
    $i->waitForElementVisible($socialBlockSettingsAssertion);
    $i->click('Done');
  }
}
