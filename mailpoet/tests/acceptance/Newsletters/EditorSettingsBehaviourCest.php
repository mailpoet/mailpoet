<?php declare(strict_types = 1);

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

  public function testSettingsBehaviour(\AcceptanceTester $i) {
    $i->wantTo('Test settings behaviour');
    $newsletterTitle = 'Settings Newsletter';
    $newsletter = (new Newsletter())
        ->withSubject($newsletterTitle)
        ->loadBodyFrom('newsletterWithALCAndButton.json')
        ->withSubject($newsletterTitle)
        ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());

    // Check settings are not visible at the beginning
    $i->dontSee(self::SETTINGS_PANEL_SELECTOR);

    // Check settings are opened and keeps opened when clicking on the same block
    $i->click(self::ALC_OVERLAY_SELECTOR);
    $i->waitForElementVisible(self::SETTINGS_PANEL_SELECTOR);
    $i->wait(0.35); // CSS animation
    $i->click(self::ALC_OVERLAY_SELECTOR);
    $i->seeElement(self::SETTINGS_PANEL_SELECTOR);

    // Check settings are closed when block is duplicated
    $i->click(self::DUPLICATE_BUTTON_SELECTOR);
    $i->waitForElementNotVisible(self::SETTINGS_PANEL_SELECTOR);
    $i->wait(1); // Wait for ALC blocks to reorder themselves

    // Check settings are closed when clicked on another block
    $i->click(self::ALC_OVERLAY_SELECTOR);
    $i->waitForElementVisible(self::SETTINGS_PANEL_SELECTOR);
    $i->wait(0.35); // CSS animation
    $i->scrollTo('[data-automation-id="text_block_in_editor"]');
    $i->moveMouseOver('[data-automation-id="alc_settings_done"]'); // To avoid flakyness
    $i->click(self::BUTTON_1_SELECTOR);
    $i->waitForElementNotVisible(self::SETTINGS_PANEL_SELECTOR);

    // Check other blocks are not highlightable when settings are showed
    $i->seeNumberOfElements(self::HIGHLIGHTED_BLOCK_SELECTOR, 0); // Nothing is highlighted
    $i->moveMouseOver(['xpath' => '//*[text()="' . self::BUTTON_2_SELECTOR . '"]']);
    $i->wait(0.35); // CSS animation
    $i->seeNumberOfElements(self::HIGHLIGHTED_BUTTON_SELECTOR, 1); // Button is highlighted
    $i->click(self::ALC_OVERLAY_SELECTOR);
    $i->wait(0.35); // CSS animation
    $i->seeNumberOfElements(self::HIGHLIGHTED_ALC_SELECTOR, 1); // ALC is highlighted
    $i->moveMouseOver(['xpath' => '//*[text()="' . self::BUTTON_1_SELECTOR . '"]']);
    $i->wait(0.35); // CSS animation
    $i->seeNumberOfElements(self::HIGHLIGHTED_ALC_SELECTOR, 1); // ALC is highlighted
    $i->seeNumberOfElements(self::HIGHLIGHTED_BUTTON_SELECTOR, 0); // Button is not highlighted
  }
}
