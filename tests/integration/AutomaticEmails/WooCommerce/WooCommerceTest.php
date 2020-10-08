<?php

namespace MailPoet\AutomaticEmails\WooCommerce;

use Codeception\Util\Stub;
use MailPoet\AutomaticEmails\AutomaticEmails;
use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct;
use MailPoet\WP\Functions as WPFunctions;

class WooCommerceTest extends \MailPoetTest {
  public function testItRegistersAbandonedCartEvent() {
    $WC = Stub::make(new WooCommerce(), ['isWoocommerceEnabled' => true]);
    $WC->__construct();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails();
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, AbandonedCart::SLUG);
    expect($result)->notEmpty();
  }

  public function testItRegistersFirstPuchaseEvent() {
    $WC = Stub::make(new WooCommerce(), ['isWoocommerceEnabled' => true]);
    $WC->__construct();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails();
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, FirstPurchase::SLUG);
    expect($result)->notEmpty();

    // event hooks are initialized
    expect(has_filter('mailpoet_newsletter_shortcode'))->true();
    expect(has_filter('woocommerce_order_status_completed'))->true();
    expect(has_filter('woocommerce_order_status_processing'))->true();
  }

  public function testItRegistersPurchasedInCategoryEvent() {
    $WC = Stub::make(new WooCommerce(), ['isWoocommerceEnabled' => true]);
    $WC->__construct();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails();
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, PurchasedInCategory::SLUG);
    expect($result)->notEmpty();
  }

  public function testItRegistersPurchasedProductEvent() {
    $WC = Stub::make(new WooCommerce(), ['isWoocommerceEnabled' => true]);
    $WC->__construct();
    $WC->init();

    // event is registered
    $AM = new AutomaticEmails();
    $result = $AM->getAutomaticEmailEventBySlug(WooCommerce::SLUG, PurchasedProduct::SLUG);
    expect($result)->notEmpty();

    // event hooks are initialized
    expect(has_filter('woocommerce_order_status_completed'))->true();
    expect(has_filter('woocommerce_order_status_processing'))->true();
    expect(has_filter('woocommerce_product_purchased_get_products'))->true();
  }

  public function testItReplacesEventActionButtonWithLinkToWCPluginRepoWhenWCIsDisabled() {
    $WC = Stub::make(new WooCommerce(), ['isWoocommerceEnabled' => false]);
    $WC->__construct();
    $WC->init();
    $AM = new AutomaticEmails();
    $result = $AM->getAutomaticEmailBySlug('woocommerce');
    foreach ($result['events'] as $event) {
      expect($event['actionButtonTitle'])->equals('WooCommerce is required');
      expect($event['actionButtonLink'])->equals('https://wordpress.org/plugins/woocommerce/');
    }

    $WC = Stub::make(new WooCommerce(), ['isWoocommerceEnabled' => true]);
    $WC->__construct();
    $WC->init();
    $AM = new AutomaticEmails();
    $result = $AM->getAutomaticEmailBySlug('woocommerce');
    foreach ($result['events'] as $event) {
      expect($event)->hasNotKey('actionButtonTitle');
      expect($event)->hasNotKey('actionButtonLink');
    }
  }

  public function _after() {
    $wp = new WPFunctions;
    $wp->removeAllFilters('mailpoet_newsletter_shortcode');
    $wp->removeAllFilters('woocommerce_payment_complete');
    $wp->removeAllFilters('woocommerce_product_purchased_get_products');
    $wp->removeAllFilters('mailpoet_automatic_email_woocommerce');
  }
}
