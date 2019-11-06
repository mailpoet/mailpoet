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

  /** @var int */
  private $woocommerce_email_template_id;

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->features = new Features;
    $this->settings = new Settings();
    $this->features->withFeatureEnabled(FeaturesController::WC_TRANSACTIONAL_EMAILS_CUSTOMIZER);

    // TODO: remove next 4 lines when WC_TRANSACTIONAL_EMAILS_CUSTOMIZER flag is removed
    $I->login();
    $I->amOnPluginsPage();
    $I->deactivatePlugin('mailpoet');
    $I->activatePlugin('mailpoet');
  }

  function openEmailCustomizerWhenSettingIsEnabled(\AcceptanceTester $I) {
    $I->wantTo('Open WooCommerce email customizer while setting is enabled');

    $this->createEmailTemplate($I);
    $this->woocommerce_email_template_id = $this->getWooCommerceEmailTemplateId($I);

    $this->settings->withWooCommerceEmailCustomizerEnabled();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');

    $button_selector = '[data-automation-id="customize_woocommerce"]';
    $I->seeNoJSErrors();
    $I->waitForText('Customize', 30);
    $I->dontSee('Activate', $button_selector);
    $I->click($button_selector);

    $I->waitForText('Edit template for WooCommerce emails');
    $I->seeInCurrentUrl('?page=mailpoet-newsletter-editor&id=' . $this->woocommerce_email_template_id);
  }

  function openEmailCustomizerWhenSettingIsDisabled(\AcceptanceTester $I) {
    $I->wantTo('Open WooCommerce email customizer while setting is disabled');

    $this->settings->withWooCommerceEmailCustomizerDisabled();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');

    $button_selector = '[data-automation-id="customize_woocommerce"]';
    $I->seeNoJSErrors();
    $I->waitForText('Activate & Customize', 30);
    $I->click($button_selector);

    $I->waitForText('Edit template for WooCommerce emails');
    $this->woocommerce_email_template_id = $this->getWooCommerceEmailTemplateId($I);
    $I->seeInCurrentUrl('?page=mailpoet-newsletter-editor&id=' . $this->woocommerce_email_template_id);
  }

  function showNoticeWhenWooCommerceCustomizerIsDisabled(\AcceptanceTester $I) {
    $I->wantTo('Show notice when WooCommerce Customizer is disabled');

    $this->createEmailTemplate($I);
    $this->woocommerce_email_template_id = $this->getWooCommerceEmailTemplateId($I);

    $this->settings->withWooCommerceEmailCustomizerDisabled();

    $I->login();
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $this->woocommerce_email_template_id);
    $I->waitForText('You need to enable MailPoet email customizer for WooCommerce if you want to access to the customizer.');
    $I->seeInCurrentUrl('?page=mailpoet-settings&enable-customizer-notice#woocommerce');
  }

  private function createEmailTemplate(\AcceptanceTester $I) {
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');
    $button_selector = '[data-automation-id="customize_woocommerce"]';
    $I->click($button_selector);
    $I->waitForText('Edit template for WooCommerce emails');
  }

  private function getWooCommerceEmailTemplateId(\AcceptanceTester $I) {
    $woocommerce_settings = $I->grabFromDatabase(MP_SETTINGS_TABLE, 'value', ['name' => 'woocommerce']);
    $woocommerce_settings = unserialize($woocommerce_settings);
    return $woocommerce_settings['transactional_email_id'];
  }
}
