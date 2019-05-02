<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorSettingsBehaviourCest {

  const ALC_OVERLAY_SELECTOR = '[data-automation-id="alc_overlay"]';
  const DUPLICATE_BUTTON_SELECTOR = '[data-automation-id="duplicate_tool"]';
  const SETTINGS_PANEL_SELECTOR = '#mailpoet_panel';

  function testSettingsBehaviour(\AcceptanceTester $I) {
    $I->wantTo('Test settings behaviour');
    $newsletterTitle = 'Settings Newsletter';
    (new Newsletter())->withSubject($newsletterTitle)->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    $I->waitForElementNotVisible('.velocity-animating');

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
  }

}
