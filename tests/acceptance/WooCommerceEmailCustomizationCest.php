<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;

class WooCommerceEmailCustomizationCest {

  /** @var Settings */
  private $settings;

  /** @var Features */
  private $features;

  function _before() {
    $this->features = new Features;
    $this->settings = new Settings();
    $this->features->withFeatureEnabled('wc-transactional-emails-customizer');
  }

  function openEmailCustomizerWhenSettingIsEnabled(\AcceptanceTester $I) {
    $I->wantTo('Open WooCommerce email customizer while setting is enabled');

    $I->activateWooCommerce();
    $this->settings->withWooCommerceEmailCustomizerEnabled();
    $this->settings->withWooCommerceTransactionalEmailId(11);

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');

    $button_selector = '[data-automation-id="customize_woocommerce"]';
    $I->seeNoJSErrors();
    $I->waitForText('Customize', 30);
    $I->dontSee('Activate', $button_selector);
    $I->click($button_selector);
    $I->seeInCurrentUrl('?page=mailpoet-newsletter-editor&id=11');
  }

  function openEmailCustomizerWhenSettingIsDisabled(\AcceptanceTester $I) {
    $I->wantTo('Open WooCommerce email customizer while setting is disabled');

    $I->activateWooCommerce();
    $this->settings->withWooCommerceEmailCustomizerDisabled();
    $this->settings->withWooCommerceTransactionalEmailId(11);

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');

    $button_selector = '[data-automation-id="customize_woocommerce"]';
    $I->seeNoJSErrors();
    $I->waitForText('Activate & Customize', 30);
    $I->click($button_selector);
    $I->seeInCurrentUrl('?page=mailpoet-newsletter-editor&id=11');
  }
}
