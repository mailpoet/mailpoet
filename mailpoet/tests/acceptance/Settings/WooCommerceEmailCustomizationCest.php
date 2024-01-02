<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use PHPUnit\Framework\Assert;

/**
 * @group woo
 */
class WooCommerceEmailCustomizationCest {

  /** @var Settings */
  private $settings;

  /** @var int */
  private $woocommerceEmailTemplateId;

  /** @var string */
  private $wcCustomizerDisabledMessage;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->settings = new Settings();

    $this->wcCustomizerDisabledMessage = 'The usage of this email template for your WooCommerce emails is not yet activated.';
  }

  public function openEmailCustomizerWhenSettingIsEnabled(\AcceptanceTester $i) {
    $i->wantTo('Open WooCommerce email customizer while setting is enabled');

    $this->createEmailTemplate($i);
    $this->woocommerceEmailTemplateId = $this->getWooCommerceEmailTemplateId($i);

    $this->settings->withWooCommerceEmailCustomizerEnabled();

    $i->amOnMailpoetPage('Settings#/woocommerce');
    $i->click('Open template editor');

    $i->waitForText('Edit template for WooCommerce emails');
    $i->seeInCurrentUrl('?page=mailpoet-newsletter-editor&id=' . $this->woocommerceEmailTemplateId);

    $i->dontSee($this->wcCustomizerDisabledMessage);
  }

  public function openEmailCustomizerWhenSettingIsDisabled(\AcceptanceTester $i) {
    $i->wantTo('Open WooCommerce email customizer while setting is disabled');

    $this->createEmailTemplate($i);
    $this->woocommerceEmailTemplateId = $this->getWooCommerceEmailTemplateId($i);

    $this->settings->withWooCommerceEmailCustomizerDisabled();

    $i->amOnMailpoetPage('Emails');
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $this->woocommerceEmailTemplateId);
    $i->waitForText('Edit template for WooCommerce emails');

    $activationSelector = '.mailpoet_save_woocommerce_customizer_disabled';
    $i->see($this->wcCustomizerDisabledMessage, $activationSelector);
    $i->click('Activate now', $activationSelector);
    $i->waitForElementNotVisible($activationSelector);
  }

  private function createEmailTemplate(\AcceptanceTester $i) {
    $i->login();
    $i->amOnMailpoetPage('Settings#/woocommerce');
    $i->click('Open template editor');
    $i->waitForText('Edit template for WooCommerce emails');
  }

  private function getWooCommerceEmailTemplateId(\AcceptanceTester $i) {
    $woocommerceSettings = $i->grabFromDatabase(MP_SETTINGS_TABLE, 'value', ['name' => 'woocommerce']);
    Assert::assertIsString($woocommerceSettings);
    $woocommerceSettings = unserialize($woocommerceSettings);
    Assert::assertIsArray($woocommerceSettings);
    return $woocommerceSettings['transactional_email_id'];
  }
}
