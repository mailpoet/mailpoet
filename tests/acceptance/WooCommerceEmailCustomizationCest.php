<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class WooCommerceEmailCustomizationCest {

  /** @var Settings */
  private $settings;

  /** @var int */
  private $woocommerce_email_template_id;

  /** @var string */
  private $wc_customizer_disabled_message;

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->settings = new Settings();

    $this->wc_customizer_disabled_message = 'The usage of this email template for your WooCommerce emails is not yet activated.';
  }

  function openEmailCustomizerWhenSettingIsEnabled(\AcceptanceTester $I) {
    $I->wantTo('Open WooCommerce email customizer while setting is enabled');

    $this->createEmailTemplate($I);
    $this->woocommerce_email_template_id = $this->getWooCommerceEmailTemplateId($I);

    $this->settings->withWooCommerceEmailCustomizerEnabled();

    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');

    $button_selector = '[data-automation-id="customize_woocommerce"]';
    $I->seeNoJSErrors();
    $I->waitForText('Customize', 30);
    $I->dontSee('Activate', $button_selector);
    $I->click($button_selector);

    $I->waitForText('Edit template for WooCommerce emails');
    $I->seeInCurrentUrl('?page=mailpoet-newsletter-editor&id=' . $this->woocommerce_email_template_id);

    $I->dontSee($this->wc_customizer_disabled_message);
  }

  function openEmailCustomizerWhenSettingIsDisabled(\AcceptanceTester $I) {
    $I->wantTo('Open WooCommerce email customizer while setting is disabled');

    $this->createEmailTemplate($I);
    $this->woocommerce_email_template_id = $this->getWooCommerceEmailTemplateId($I);

    $this->settings->withWooCommerceEmailCustomizerDisabled();

    $I->amOnMailpoetPage('Emails');
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $this->woocommerce_email_template_id);
    $I->waitForText('Edit template for WooCommerce emails');

    $activation_selector = '.mailpoet_save_woocommerce_customizer_disabled';
    $I->see($this->wc_customizer_disabled_message, $activation_selector);
    $I->click('Activate now', $activation_selector);
    $I->waitForElementNotVisible($activation_selector);
  }

  private function createEmailTemplate(\AcceptanceTester $I) {
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="customize_woocommerce"]');
    $I->waitForText('Edit template for WooCommerce emails');
  }

  private function getWooCommerceEmailTemplateId(\AcceptanceTester $I) {
    $woocommerce_settings = $I->grabFromDatabase(MP_SETTINGS_TABLE, 'value', ['name' => 'woocommerce']);
    $woocommerce_settings = unserialize($woocommerce_settings);
    return $woocommerce_settings['transactional_email_id'];
  }
}
