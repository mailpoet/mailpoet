<?php

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

  protected function _inject(Settings $settings) {
    $this->settings = $settings;
  }

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->settings->withWooCommerceListImportPageDisplayed(true);
    $this->settings->withCookieRevenueTrackingDisabled();
  }

  function showWooCommercePurchaseStatistics(\AcceptanceTester $I) {
    $title = 'Newsletter Title';
    $currency = 'EUR';
    $I->cli('wc setting update woocommerce_currency --value ' . $currency);

    $newsletter = $this->createNewsletter($title);
    $click1 = $this->createClickInNewsletter($newsletter);
    $click2 = $this->createClickInNewsletter($newsletter);

    // order 1: EUR
    $woocommerce_order = $this->createWooCommerceOrder($I, $currency, 1);
    (new StatisticsWooCommercePurchases($click1, $woocommerce_order))->create();

    // order 2: EUR, two clicks from two subscribers
    $woocommerce_order = $this->createWooCommerceOrder($I, $currency, 2);
    (new StatisticsWooCommercePurchases($click1, $woocommerce_order))->create();
    (new StatisticsWooCommercePurchases($click2, $woocommerce_order))->create();

    // order 3: USD
    $woocommerce_order = $this->createWooCommerceOrder($I, 'USD', 100);
    (new StatisticsWooCommercePurchases($click1, $woocommerce_order))->create();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($title);
    $I->see('5,00€', '.mailpoet_stats_text a');
  }

  function dontShowWooCommercePurchaseStatisticsWithZeroValue(\AcceptanceTester $I) {
    $title = 'Newsletter Title';
    $currency = 'EUR';
    $I->cli('wc setting update woocommerce_currency --value ' . $currency);

    $newsletter = $this->createNewsletter($title);
    $click = $this->createClickInNewsletter($newsletter);

    // order with zero value
    $woocommerce_order = $this->createWooCommerceOrder($I, $currency, 0);
    (new StatisticsWooCommercePurchases($click, $woocommerce_order))->create();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($title);
    $I->dontSee('€', '.mailpoet_stats_text');
  }

  function _after(\AcceptanceTester $I) {
    (new WooCommerceOrder($I))->deleteAll();
    $I->deactivateWooCommerce();
    $I->cli('db query "TRUNCATE TABLE mp_mailpoet_newsletters" --allow-root');
    $I->cli('db query "TRUNCATE TABLE mp_mailpoet_sending_queue" --allow-root');
    $I->cli('db query "TRUNCATE TABLE mp_mailpoet_subscribers" --allow-root');
    $I->cli('db query "TRUNCATE TABLE mp_mailpoet_newsletter_links" --allow-root');
    $I->cli('db query "TRUNCATE TABLE mp_mailpoet_statistics_clicks" --allow-root');
    $I->cli('db query "TRUNCATE TABLE mp_mailpoet_statistics_woocommerce_purchases" --allow-root');
  }

  private function createNewsletter($newsletter_title) {
    return (new Newsletter())
      ->withSubject($newsletter_title)
      ->loadBodyFrom('newsletterWithText.json')
      ->withSentStatus()
      ->withActiveStatus()
      ->withSendingQueue()
      ->create();
  }

  private function createClickInNewsletter($newsletter) {
    $subscriber = (new Subscriber())->create();
    $newsletter_link = (new NewsletterLink($newsletter))->create();
    return (new StatisticsClicks($newsletter_link, $subscriber))->create();
  }

  private function createWooCommerceOrder(\AcceptanceTester $I, $currency, $product_price) {
    return (new WooCommerceOrder($I))
      ->withStatus(WooCommerceOrder::STATUS_COMPLETED)
      ->withCurrency($currency)
      ->withProducts([
        [
          'id' => 1,
          'name' => 'Product 1',
          'total' => $product_price,
        ],
      ])
      ->create();
  }
}
