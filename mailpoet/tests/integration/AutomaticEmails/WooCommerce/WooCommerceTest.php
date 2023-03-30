<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails\WooCommerce;

use MailPoet\AutomaticEmails\AutomaticEmailFactory;
use MailPoet\AutomaticEmails\AutomaticEmails;
use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class WooCommerceTest extends \MailPoetTest {
  /** @var WooCommerceEventFactory */
  private $wooCommerceEventFactory;

  /** @var AutomaticEmailFactory */
  private $automaticEmailFactory;

  public function _before() {
    $wp = new WPFunctions();
    $this->wooCommerceEventFactory = $this->diContainer->get(WooCommerceEventFactory::class);
    $this->automaticEmailFactory = $this->makeEmpty(AutomaticEmailFactory::class, [
      'createWooCommerceEmail' => new WooCommerce($wp, new Helper($wp), $this->wooCommerceEventFactory),
    ]);
  }

  public function testItRegistersAbandonedCartEvent() {
    $WC = $this->createWooCommerceEmailMock();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails(new WPFunctions(), $this->automaticEmailFactory);
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, AbandonedCart::SLUG);
    expect($result)->notEmpty();
  }

  public function testItRegistersFirstPuchaseEvent() {
    $WC = $this->createWooCommerceEmailMock();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails(new WPFunctions(), $this->automaticEmailFactory);
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, FirstPurchase::SLUG);
    expect($result)->notEmpty();

    // event hooks are initialized
    expect(has_filter('mailpoet_newsletter_shortcode'))->true();
    expect(has_filter('woocommerce_order_status_completed'))->true();
    expect(has_filter('woocommerce_order_status_processing'))->true();
  }

  public function testItRegistersPurchasedInCategoryEvent() {
    $WC = $this->createWooCommerceEmailMock();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails(new WPFunctions(), $this->automaticEmailFactory);
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, PurchasedInCategory::SLUG);
    expect($result)->notEmpty();
  }

  public function testItRegistersPurchasedProductEvent() {
    $WC = $this->createWooCommerceEmailMock();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails(new WPFunctions(), $this->automaticEmailFactory);
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, PurchasedProduct::SLUG);
    expect($result)->notEmpty();

    // event hooks are initialized
    expect(has_filter('woocommerce_order_status_completed'))->true();
    expect(has_filter('woocommerce_order_status_processing'))->true();
    expect(has_filter('woocommerce_product_purchased_get_products'))->true();
  }

  public function testItReplacesEventActionButtonWithLinkToWCPluginRepoWhenWCIsDisabled() {
    $WC = $this->createWooCommerceEmailMock(false);
    $WC->init();

    $AM = new AutomaticEmails(new WPFunctions(), $this->automaticEmailFactory);
    $result = $AM->getAutomaticEmailBySlug('woocommerce');
    foreach ($result['events'] as $event) {
      expect($event['actionButtonTitle'])->equals('WooCommerce is required');
      expect($event['actionButtonLink'])->equals('https://wordpress.org/plugins/woocommerce/');
    }

    $WC = $this->createWooCommerceEmailMock();
    $WC->init();

    $AM = new AutomaticEmails(new WPFunctions(), $this->automaticEmailFactory);
    $result = $AM->getAutomaticEmailBySlug('woocommerce');
    foreach ($result['events'] as $event) {
      expect($event)->hasNotKey('actionButtonTitle');
      expect($event)->hasNotKey('actionButtonLink');
    }
  }

  public function _after() {
    parent::_after();
    $wp = new WPFunctions;
    $wp->removeAllFilters('mailpoet_newsletter_shortcode');
    $wp->removeAllFilters('woocommerce_payment_complete');
    $wp->removeAllFilters('woocommerce_product_purchased_get_products');
    $wp->removeAllFilters('mailpoet_automatic_email_woocommerce');
  }

  private function createWooCommerceEmailMock(bool $isWoocommerceEnabled = true): WooCommerce {
    $wp = new WPFunctions();
    $mock = $this->make(WooCommerce::class, ['isWoocommerceEnabled' => $isWoocommerceEnabled]);
    $mock->__construct($wp, new Helper($wp), $this->wooCommerceEventFactory);
    return $mock;
  }
}
