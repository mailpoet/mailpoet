<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * @group woo
 */
class WooCommerceSettingsTabCest {

  const CUSTOMIZE_SELECTOR = '[data-automation-id="mailpoet_woocommerce_customize"]';
  const DISABLE_SELECTOR = '[data-automation-id="mailpoet_woocommerce_disable"]';

  /** @var Settings */
  private $settingsFactory;

  /** @var array WooCommerce Product data*/
  private $product;
  
  /** @var string */
  private $segmentName;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->product = (new WooCommerceProduct($i))->create();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->segmentName = 'Additional WC List';
    $segmentFactory = new Segment();
    $segmentFactory->withName($this->segmentName)->create();
  }

  public function checkWooCommerceOptInMessage(\AcceptanceTester $i) {
    $i->wantTo('Check WooCommerce Opt-in message');

    $i->login();
    $i->amOnMailpoetPage('Settings');
    $i->waitForText('WooCommerce');
    $i->click('[data-automation-id="woocommerce_settings_tab"]');
    $i->waitForText('Opt-in on checkout');
    $i->seeInField('[data-automation-id="mailpoet_wc_checkout_optin_message"]', 'I would like to receive exclusive emails with discounts and product information');

    $i->wantTo('Change the opt-in message and verify on the front-end');

    $i->fillField('[data-automation-id="mailpoet_wc_checkout_optin_message"]', 'I want to opt-in with the custom message.');
    $i->click('Save settings');
    $i->waitForText('Settings saved');
    $i->addProductToCart($this->product);
    $i->goToCheckout();
    $i->see('I want to opt-in with the custom message.');
  }

  public function checkWooCommerceAdditionalLists(\AcceptanceTester $i) {
    $i->wantTo('Check adding additional lists to subscribe beside WooCommerce Customers');

    $customerEmail = 'woo_customer@example.com';

    $i->login();
    $i->amOnMailpoetPage('Settings');
    $i->waitForText('WooCommerce');
    $i->click('[data-automation-id="woocommerce_settings_tab"]');
    $i->waitForText('Opt-in on checkout');
    $i->selectOptionInReactSelect($this->segmentName, '#mailpoet_wc_checkout_optin_segments');
    $i->click('Save settings');
    $i->waitForText('Settings saved');
    $i->logOut();
    $i->orderProduct($this->product, $customerEmail, false, true);
    $i->login();
    $i->checkSubscriberStatusAndLists($customerEmail, SubscriberEntity::STATUS_UNCONFIRMED, ['WooCommerce Customers', $this->segmentName]);
  }

  public function checkWooCommerceTabExists(\AcceptanceTester $i) {
    $i->wantTo('Check WooCommerce settings tab exists when the WooCommerce plugin is active');

    $i->login();
    $i->amOnMailpoetPage('Settings');
    $i->waitForText('WooCommerce');
    $i->click('[data-automation-id="woocommerce_settings_tab"]');
    $i->waitForText('Opt-in on checkout');
    $i->seeNoJSErrors();

    // The tab is hidden when WooCommerce is deactivated
    $i->deactivateWooCommerce();
    $i->amOnMailpoetPage('Settings');
    $i->dontSeeElement('[data-automation-id="woocommerce_settings_tab"]');
    $i->seeNoJSErrors();
  }

  public function checkWooCommercePluginSettingsAreDisabled(\AcceptanceTester $i) {
    $this->settingsFactory->withWooCommerceEmailCustomizerEnabled();

    $i->wantTo('Check WooCommerce plugin email settings are overlayed with link to MailPoet');

    $i->login();
    $i->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=general");
    $i->dontSeeElementInDOM(self::CUSTOMIZE_SELECTOR);

    $i->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=email");
    $i->scrollTo(self::CUSTOMIZE_SELECTOR);
    $href = $i->grabAttributeFrom(self::CUSTOMIZE_SELECTOR, 'href');
    verify($href)->stringContainsString('?page=mailpoet-newsletter-editor&id=');
    $href = $i->grabAttributeFrom(self::DISABLE_SELECTOR, 'href');
    verify($href)->stringContainsString('?page=mailpoet-settings#woocommerce');

    $i->amOnPage("/wp-admin/admin.php?page=wc-settings&tab=email&section=wc_email_new_order");
    $i->dontSeeElementInDOM(self::CUSTOMIZE_SELECTOR);
  }
}
