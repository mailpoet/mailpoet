<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorSettingsBehaviourCest {

  const ALC_OVERLAY_SELECTOR = '[data-automation-id="alc_overlay"]';
  const BUTTON_1_SELECTOR = 'Click me';
  const BUTTON_2_SELECTOR = 'Push me';
  const DUPLICATE_BUTTON_SELECTOR = '[data-automation-id="duplicate_tool"]';
  const HIGHLIGHTED_BLOCK_SELECTOR = '.mailpoet_highlight';
  const HIGHLIGHTED_BUTTON_SELECTOR = '.mailpoet_highlight > .mailpoet_content > .mailpoet_editor_button';
  const HIGHLIGHTED_ALC_SELECTOR = '.mailpoet_highlight .mailpoet_automated_latest_content_block_posts';
  const SETTINGS_PANEL_SELECTOR = '#mailpoet_panel';

  public function testSettingsBehaviour(\AcceptanceTester $I) {
    $I->wantTo('Test settings behaviour');
    $newsletterTitle = 'Settings Newsletter';
    $newsletter = (new Newsletter())
        ->withSubject($newsletterTitle)
        ->loadBodyFrom('newsletterWithALCAndButton.json')
        ->withSubject($newsletterTitle)
        ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);

    // Check settings are not visible at the beginning
    $I->dontSee(self::SETTINGS_PANEL_SELECTOR);

    // Check settings are opened and keeps opened when clicking on the same block
    $I->click(self::ALC_OVERLAY_SELECTOR);
    $I->waitForElementVisible(self::SETTINGS_PANEL_SELECTOR);
    $I->wait(0.35); // CSS animation
    $I->click(self::ALC_OVERLAY_SELECTOR);
    $I->seeElement(self::SETTINGS_PANEL_SELECTOR);

    // Check settings are closed when block is duplicated
    $I->click(self::DUPLICATE_BUTTON_SELECTOR);
    $I->waitForElementNotVisible(self::SETTINGS_PANEL_SELECTOR);
    $I->wait(1); // Wait for ALC blocks to reorder themselves

    // Check settings are closed when clicked on another block
    $I->click(self::ALC_OVERLAY_SELECTOR);
    $I->waitForElementVisible(self::SETTINGS_PANEL_SELECTOR);
    $I->wait(0.35); // CSS animation
    $I->click(self::BUTTON_1_SELECTOR);
    $I->waitForElementNotVisible(self::SETTINGS_PANEL_SELECTOR);

    // Check other blocks are not highlightable when settings are showed
    $I->seeNumberOfElements(self::HIGHLIGHTED_BLOCK_SELECTOR, 0); // Nothing is highlighted
    $I->moveMouseOver(['xpath' => '//*[text()="' . self::BUTTON_2_SELECTOR . '"]']);
    $I->wait(0.35); // CSS animation
    $I->seeNumberOfElements(self::HIGHLIGHTED_BUTTON_SELECTOR, 1); // Button is highlighted
    $I->click(self::ALC_OVERLAY_SELECTOR);
    $I->wait(0.35); // CSS animation
    $I->seeNumberOfElements(self::HIGHLIGHTED_ALC_SELECTOR, 1); // ALC is highlighted
    $I->moveMouseOver(['xpath' => '//*[text()="' . self::BUTTON_1_SELECTOR . '"]']);
    $I->wait(0.35); // CSS animation
    $I->seeNumberOfElements(self::HIGHLIGHTED_ALC_SELECTOR, 1); // ALC is highlighted
    $I->seeNumberOfElements(self::HIGHLIGHTED_BUTTON_SELECTOR, 0); // Button is not highlighted
  }

}
