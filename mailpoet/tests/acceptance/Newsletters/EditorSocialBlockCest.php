<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorSocialBlockCest {
  public function addSocialBlock(\AcceptanceTester $i) {
    $i->wantTo('add social block to newsletter');
    $headerSettingsAssertion = ('[data-automation-id="social_done_button"]');
    $socialBlockInEditor = ('[data-automation-id="socialBlock"]');
    $socialBlockSettingsAssertion = ('[data-automation-id="social_select_another_network"]');
    $footer = '[data-automation-id="footer"]';
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_social', '#mce_1');
    //Prevent flakyness by adding footer mouse over
    $i->moveMouseOver($footer, 3, 2);
    //Open settings by clicking on block
    $i->moveMouseOver($socialBlockInEditor, 3, 2);
    $i->waitForText('View this in your browser.');
    $i->clickWithLeftButton(); // Clicks on the position where mouse cursor is
    $i->wait(0.35); // CSS animation
    $i->scrollTo($headerSettingsAssertion);
    $i->waitForElementVisible($headerSettingsAssertion);
    $i->click($headerSettingsAssertion);
  }
}
