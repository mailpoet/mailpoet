<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\User;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Test\DataFactories\WooCommerceSubscription;

/**
 * @group woo
 */
class WooCommerceSubscriptionsSegmentCest {
  public function _before(\AcceptanceTester $i, $scenario) {
    if (!$i->canTestWithPlugin(\AcceptanceTester::WOO_COMMERCE_SUBSCRIPTIONS_PLUGIN)) {
      $scenario->skip('Can‘t test without woocommerce-subscriptions');
    }
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
    $subscriptionProduct2 = $productFactory
      ->withName('Subscription 2')
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
    $userFactory->createUser('Sub Scriber3', 'subscriber', 'subscriber3@example.com');
    $userFactory->createUser('Sub Scriber4', 'subscriber', 'subscriber4@example.com');

    $subscriptionFactory = new WooCommerceSubscription();
    $subscriptionFactory->createSubscription($subscriber1->ID, $subscriptionProduct1['id']);
    $subscriptionFactory->createSubscription($subscriber2->ID, $subscriptionProduct1['id']);
    $subscriptionFactory->createSubscription($subscriber2->ID, $subscriptionProduct2['id']);

    $segmentActionSelectElement = '[data-automation-id="select-segment-action"]';
    $operatorSelectElement = '[data-automation-id="select-operator"]';
    $segmentTitle = 'Woo Active Subscriptions';
    $i->wantTo('Create dynamic segment for subscriptions');
    $i->login();
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="new-segment"]');
    $i->fillField(['name' => 'name'], $segmentTitle);
    $i->fillField(['name' => 'description'], 'Desc ' . $segmentTitle);
    $i->selectOptionInReactSelect('has active subscription', $segmentActionSelectElement);
    $i->selectOptionInReactSelect('Subscription 1', '[data-automation-id="select-segment-products"]');
    $i->selectOptionInReactSelect('Subscription 2', '[data-automation-id="select-segment-products"]');
    $i->waitForText('This segment has');

    // Check for none of
    $i->selectOption($operatorSelectElement, 'none of');
    $i->waitForText('This segment has 3 subscribers.'); // subscriber3@example.com, subscriber4@example.com, and admin user
    // Check for all of
    $i->selectOption($operatorSelectElement, 'all of');
    $i->waitForText('This segment has 1 subscribers.'); // subscriber2@example.com
    // Check for any of
    $i->selectOption($operatorSelectElement, 'any of'); // subscriber2@example.com and subscriber1@example.com
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
    $i->fillField("$segmentActionSelectElement input", 'has active subscription');
    $i->canSee('No options', $segmentActionSelectElement);
  }
}
