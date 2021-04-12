<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\User;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Test\DataFactories\WooCommerceSubscription;

class WooCommerceSubscriptionsSegmentCest {
  public function _before(\AcceptanceTester $i) {
    (new Settings())->withWooCommerceListImportPageDisplayed(true);
    (new Settings())->withCookieRevenueTrackingDisabled();
    $i->activateWooCommerce();
  }

  public function _after(\AcceptanceTester $i) {
    $i->deactivateWooCommerce();
  }

  public function createSubscriptionSegmentForActiveSubscriptions(\AcceptanceTester $i) {
    $i->activateWooCommerceSubscriptions();
    $productFactory = new WooCommerceProduct($i);
    $subscriptionProduct1 = $productFactory
      ->withName('Subscription 1')
      ->withType(WooCommerceProduct::TYPE_SUBSCRIPTION)
      ->withPrice(10)
      ->create();
    $productFactory
      ->withName('Subscription Variable')
      ->withType(WooCommerceProduct::TYPE_VARIABLE_SUBSCRIPTION)
      ->withPrice(20)
      ->create();

    $userFactory = new User();
    $subscriber1 = $userFactory->createUser('Sub Scriber1', 'subscriber', 'subscriber1@example.com');
    $subscriber2 = $userFactory->createUser('Sub Scriber2', 'subscriber', 'subscriber2@example.com');
    $subscriptionFactory = new WooCommerceSubscription();
    $subscriptionFactory->createSubscription($subscriber1->ID, $subscriptionProduct1['id']);
    $subscriptionFactory->createSubscription($subscriber2->ID, $subscriptionProduct1['id']);

    $segmentActionSelectElement = '[data-automation-id="select-segment-action"]';
    $segmentTitle = 'Woo Active Subscriptions';
    $i->wantTo('Create dynamic segment for subscriptions');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'Desc ' . $segmentTitle);
    $i->selectOptionInReactSelect('has an active subscription', $segmentActionSelectElement);
    $i->selectOptionInReactSelect('Subscription 1', '[data-automation-id="segment-woo-subscription-action"]');
    $i->waitForText('Calculating segment size…');
    $i->waitForText('This segment has 2 subscribers.');
    $i->seeNoJSErrors();
    $i->click('Save');
    $i->wantTo('Check that segment contains correct subscribers');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->waitForText($segmentTitle);
    $i->clickItemRowActionByItemName($segmentTitle, 'View Subscribers');
    $i->waitForText('subscriber1@example.com');
    $i->waitForText('subscriber2@example.com');

    $i->wantTo('Check that MailPoet plugin works when admin disables WooCommerce Subscriptions');
    $i->deactivateWooCommerceSubscriptions();
    $i->amOnMailpoetPage('Lists');
    $i->waitForElement('[data-automation-id="dynamic-segments-tab"]');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText($segmentTitle);
    $i->canSee('Activate the WooCommerce Subscriptions plugin to see the number of subscribers and enable the editing of this segment.');

    $i->wantTo('Check that admin can‘t add new subscriptions segment when WooCommerce Subscriptions is not active');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement($segmentActionSelectElement);
    $i->fillField("$segmentActionSelectElement input", 'has an active subscription');
    $i->canSee('No options', $segmentActionSelectElement);
  }
}
