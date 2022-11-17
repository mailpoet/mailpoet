<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsWooCommercePurchases;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\WooCommerceOrder;

class NewsletterStatisticsCest {

  /** @var Settings */
  private $settings;

  public function _before(\AcceptanceTester $i) {
    $this->settings = new Settings();
    $i->activateWooCommerce();
    $this->settings->withWooCommerceListImportPageDisplayed(true);
    $this->settings->withCookieRevenueTrackingDisabled();
  }

  public function showWooCommercePurchaseStatistics(\AcceptanceTester $i) {
    $title = 'Newsletter Title';
    $currency = 'EUR';
    $i->cli(['option', 'set', 'woocommerce_currency', $currency]);

    $newsletter = $this->createNewsletter($title);
    $click1 = $this->createClickInNewsletter($newsletter);
    $click2 = $this->createClickInNewsletter($newsletter);

    // order 1: EUR
    $woocommerceOrder = $this->createWooCommerceOrder($i, $currency, 1);
    (new StatisticsWooCommercePurchases($click1, $woocommerceOrder))->create();

    // order 2: EUR, two clicks from two subscribers
    $woocommerceOrder = $this->createWooCommerceOrder($i, $currency, 2);
    (new StatisticsWooCommercePurchases($click1, $woocommerceOrder))->create();
    (new StatisticsWooCommercePurchases($click2, $woocommerceOrder))->create();

    // order 3: USD
    $woocommerceOrder = $this->createWooCommerceOrder($i, 'USD', 100);
    (new StatisticsWooCommercePurchases($click1, $woocommerceOrder))->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($title);
    $i->see('5,00€', '.mailpoet-tag');
  }

  public function dontShowWooCommercePurchaseStatisticsWithZeroValue(\AcceptanceTester $i) {
    $title = 'Newsletter Title';
    $currency = 'EUR';
    $i->cli(['option', 'set', 'woocommerce_currency', $currency]);

    $newsletter = $this->createNewsletter($title);
    $click = $this->createClickInNewsletter($newsletter);

    // order with zero value
    $woocommerceOrder = $this->createWooCommerceOrder($i, $currency, 0);
    (new StatisticsWooCommercePurchases($click, $woocommerceOrder))->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($title);
    $i->dontSee('€', '.mailpoet-tag');
  }

  private function createNewsletter($newsletterTitle) {
    return (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->withSentStatus()
      ->withActiveStatus()
      ->withSendingQueue()
      ->create();
  }

  private function createClickInNewsletter($newsletter) {
    $subscriber = (new Subscriber())->create();
    $newsletterLink = (new NewsletterLink($newsletter))->create();
    return (new StatisticsClicks($newsletterLink, $subscriber))->create();
  }

  private function createWooCommerceOrder(\AcceptanceTester $i, $currency, $productPrice) {
    return (new WooCommerceOrder($i))
      ->withStatus(WooCommerceOrder::STATUS_COMPLETED)
      ->withCurrency($currency)
      ->withProducts([
        [
          'id' => 1,
          'name' => 'Product 1',
          'total' => $productPrice,
        ],
      ])
      ->create();
  }
}
