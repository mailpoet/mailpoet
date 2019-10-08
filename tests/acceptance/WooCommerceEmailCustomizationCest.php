<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
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
    $this->features->withFeatureEnabled(FeaturesController::WC_TRANSACTIONAL_EMAILS_CUSTOMIZER);
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

  function showNoticeWhenWooCommerceCustomizerIsDisabled(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->settings->withWooCommerceEmailCustomizerDisabled();

    // TODO: remove next 4 lines when WC_TRANSACTIONAL_EMAILS_CUSTOMIZER flag is removed
    $I->login();
    $I->amOnPluginsPage();
    $I->deactivatePlugin('mailpoet');
    $I->activatePlugin('mailpoet');

    $woocommerce_settings = $I->grabFromDatabase(MP_SETTINGS_TABLE, 'value', ['name' => 'woocommerce']);
    $woocommerce_settings = unserialize($woocommerce_settings);
    $woocommerce_email_template_id = $woocommerce_settings['transactional_email_id'];

    $I->wantTo('Show notice when WooCommerce Customizer is disabled');

    $I->login();
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $woocommerce_email_template_id);
    $I->waitForText('You need to enable MailPoet email customizer for WooCommerce if you want to access to the customizer.');
    $I->seeInCurrentUrl('?page=mailpoet-settings&enable-customizer-notice#woocommerce');
  }
}
