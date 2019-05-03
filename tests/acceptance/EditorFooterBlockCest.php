<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorFooterBlockCest {
  function addFooter(\AcceptanceTester $I) {
    $I->wantTo('add Footer block to newsletter');
    $newsletterTitle = 'Footer Block Newsletter';
    $footerInEditor = ('[data-automation-id="footer"]');
    $footerSettingsIcon = ('[data-automation-id="settings_tool"]');
    $footerSettingsAssertion = ('[data-automation-id="footer_done_button"]');
    (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithTextNoFooter.json')
      ->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    // Create Footer block
    $I->waitForText('Footer');
    $I->waitForElementNotVisible('.velocity-animating');
    $I->dragAndDrop('#automation_editor_block_footer', '#mce_0');
    //Open settings by clicking on block
    $I->moveMouseOver($footerInEditor, 3, 2);
    $I->waitForText('Manage subscription');
    $I->click($footerSettingsIcon);
    $I->waitForElementVisible($footerSettingsAssertion);
    $I->click($footerSettingsAssertion);
  }

}
