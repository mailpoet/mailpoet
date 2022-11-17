<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

/**
 * @group woo
 */
class WooCommerceDynamicSegmentsCest {
  const CATEGORY_SEGMENT = 'Purchase in category segment';
  const PRODUCT_SEGMENT = 'Purchased product segment';
  const NUMBER_OF_ORDERS_SEGMENT = 'Number of orders segment';
  const TOTAL_SPENT_SEGMENT = 'Total spent segment';
  const CUSTOMER_IN_COUNTRY = 'Customer in country segment';

  /** @var Settings */
  private $settingsFactory;

  /** @var WooCommerceProduct */
  private $productFactory;

  /** @var array */
  private $productInCategory;

  /** @var int */
  private $productCategoryId;

  /** @var SegmentEntity */
  private $categorySegment;

  /** @var SegmentEntity */
  private $productSegment;

  /** @var SegmentEntity */
  private $numberOfOrdersSegment;

  /** @var SegmentEntity */
  private $totalSpentSegment;

  /** @var SegmentEntity */
  private $customerCountrySegment;

  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->settingsFactory = new Settings();
    $this->settingsFactory->withWooCommerceListImportPageDisplayed(true);
    $this->settingsFactory->withCookieRevenueTrackingDisabled();
    $this->settingsFactory->withWooCommerceCheckoutOptinEnabled();
    $this->settingsFactory->withConfirmationEmailDisabled();

    $this->productFactory = new WooCommerceProduct($i);
    $this->productCategoryId = $this->productFactory->createCategory('Awesome stuff');
    $this->productInCategory = $this->productFactory->withCategoryIds([$this->productCategoryId])->create();

    $segmentFactory = new DynamicSegment();
    $this->productSegment = $segmentFactory
      ->withName(self::PRODUCT_SEGMENT)
      ->withWooCommerceProductFilter($this->productInCategory['id'])
      ->create();
    $this->categorySegment = $segmentFactory
      ->withName(self::CATEGORY_SEGMENT)
      ->withWooCommerceCategoryFilter($this->productCategoryId)
      ->create();
    $this->numberOfOrdersSegment = $segmentFactory
      ->withName(self::NUMBER_OF_ORDERS_SEGMENT)
      ->withWooCommerceNumberOfOrdersFilter()
      ->create();
    $this->totalSpentSegment = $segmentFactory
      ->withName(self::TOTAL_SPENT_SEGMENT)
      ->withWooCommerceTotalSpentFilter()
      ->create();
    $this->customerCountrySegment = $segmentFactory
      ->withName(self::CUSTOMER_IN_COUNTRY)
      ->withWooCommerceCustomerCountryFilter(['FR'])
      ->create();
  }

  public function addCustomerToWooCommerceSegments(\AcceptanceTester $i) {
    $i->wantTo('Check if customer who registers is added to WooCommerce dynamic segments');
    $customerEmail = 'customer_1@example.com';
    $i->orderProduct($this->productInCategory, $customerEmail);
    $guestEmail = 'guest_1@example.com';
    $i->orderProduct($this->productInCategory, $guestEmail, false);

    $i->login();

    // run action scheduler to sync customer and order data to lookup tables
    $i->wait(2);
    $i->cli(['action-scheduler', 'run', '--hooks=wc-admin_import_orders,wc-admin_import_customers --force']);

    $i->wantTo('Check subscriber is in category segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::CATEGORY_SEGMENT);
    $categorySegmentRow = "[data-automation-id='listing_item_{$this->categorySegment->getId()}']";
    $i->see('2', $categorySegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::CATEGORY_SEGMENT, 'View Subscribers');
    $i->waitForText($customerEmail);

    $i->wantTo('Check subscriber is in product segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::PRODUCT_SEGMENT);
    $productSegmentRow = "[data-automation-id='listing_item_{$this->productSegment->getId()}']";
    $i->see('2', $productSegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::PRODUCT_SEGMENT, 'View Subscribers');
    $i->waitForText($customerEmail);
    $i->waitForText($guestEmail);
  }

  public function addCustomerOnlyToCategorySegment(\AcceptanceTester $i) {
    $i->wantTo('Check if customer who registers is added to WooCommerce category and not to product segment');
    $customerEmail = 'customer_2@example.com';
    $differentProductWithCategory = $this->productFactory->withCategoryIds([$this->productCategoryId])->create();
    $i->orderProduct($differentProductWithCategory, $customerEmail);
    $guestEmail = 'guest_2@example.com';
    $i->orderProduct($differentProductWithCategory, $guestEmail, false);

    $i->login();

    // Run action scheduler wc-admin hooks to sync customer and order data to lookup tables
    // See https://github.com/woocommerce/woocommerce/blob/ba91c94ca9b1c4903964de70c8658cc7bff67d3f/plugins/woocommerce/src/Internal/Admin/Schedulers/ImportScheduler.php#L90
    $i->wait(2);
    $i->cli(['action-scheduler', 'run', '--hooks=wc-admin_import_orders,wc-admin_import_customers --force']);

    $i->wantTo('Check subscriber is in category segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::CATEGORY_SEGMENT);
    $categorySegmentRow = "[data-automation-id='listing_item_{$this->categorySegment->getId()}']";
    $i->see('2', $categorySegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::CATEGORY_SEGMENT, 'View Subscribers');
    $i->waitForText($customerEmail);
    $i->waitForText($guestEmail);

    $i->wantTo('Check subscriber is in product segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::PRODUCT_SEGMENT);
    $productSegmentRow = "[data-automation-id='listing_item_{$this->productSegment->getId()}']";
    $i->see('0', $productSegmentRow . " [data-colname='Number of subscribers']");
  }

  public function checkThatCustomersAreAddedToNumberOfOrdersSegment(\AcceptanceTester $i) {
    $i->wantTo('Check that customers are added to the number of orders segment when the number of orders they placed matches what is expected');
    $customer1Email = 'customer_2@example.com';
    $anyProduct = $this->productInCategory;
    $i->orderProduct($anyProduct, $customer1Email);

    $i->login();

    // run action scheduler to sync customer and order data to lookup tables
    $i->wait(2);
    $i->cli(['action-scheduler', 'run', '--hooks=wc-admin_import_orders,wc-admin_import_customers --force']);

    $i->wantTo('Check there is one subscriber in the number of orders segments (the segment was configured to match customers that placed one order in the last day)');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::NUMBER_OF_ORDERS_SEGMENT);
    $numberOfOrdersSegmentRow = "[data-automation-id='listing_item_{$this->numberOfOrdersSegment->getId()}']";
    $i->see('1', $numberOfOrdersSegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::NUMBER_OF_ORDERS_SEGMENT, 'View Subscribers');
    $i->waitForText($customer1Email);
  }

  public function checkThatCustomersAreAddedToTotalSpentSegment(\AcceptanceTester $i) {
    $i->wantTo('Check that customers are added to the total spent segment when the value of orders they placed matches what is expected');
    $customerEmail = 'customer_2@example.com';
    $anyProduct = $this->productInCategory;
    $i->orderProduct($anyProduct, $customerEmail);

    $i->login();

    // run action scheduler to sync customer and order data to lookup tables
    $i->wait(2);
    $i->cli(['action-scheduler', 'run', '--hooks=wc-admin_import_orders,wc-admin_import_customers --force']);

    $i->wantTo('Check that there is one subscriber in the total spent segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::TOTAL_SPENT_SEGMENT);
    $totalSpentSegmentRow = "[data-automation-id='listing_item_{$this->totalSpentSegment->getId()}']";
    $i->see('1', $totalSpentSegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::TOTAL_SPENT_SEGMENT, 'View Subscribers');
    $i->waitForText($customerEmail);
  }

  public function checkThatCustomersAreAddedToCustomerInCountrySegment(\AcceptanceTester $i) {
    $i->wantTo('Check that customers are added to the customer in country segment');
    $customerEmail = 'customer_france@example.com';
    $product = $this->productFactory->create();
    $i->orderProduct($product, $customerEmail);
    $guestEmail = 'guest_france@example.com';
    $i->orderProduct($product, $guestEmail, false);

    $i->login();

    // run action scheduler to sync customer and order data to lookup tables
    $i->wait(2);
    $i->cli(['action-scheduler', 'run', '--hooks=wc-admin_import_orders,wc-admin_import_customers --force']);

    $i->wantTo('Check that there is one subscriber in customer country segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::CUSTOMER_IN_COUNTRY);
    $totalSpentSegmentRow = "[data-automation-id='listing_item_{$this->customerCountrySegment->getId()}']";
    $i->see('2', $totalSpentSegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::CUSTOMER_IN_COUNTRY, 'View Subscribers');
    $i->waitForText($customerEmail);
    $i->waitForText($guestEmail);
  }

  public function displayMessageWhenPluginIsDeactivated(\AcceptanceTester $i) {
    $i->wantTo('Check if count of subscribers is hidden and message with plugin name is visible');
    $i->deactivateWooCommerce();
    $i->login();
    $i->wantTo('Check messages in list when WooCommerce is deactivated');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');

    $i->wantTo('Check that message is visible instead of count of subscribers');
    $i->waitForText(self::CATEGORY_SEGMENT);
    $message = 'Activate the WooCommerce plugin to see the number of subscribers and enable the editing of this segment.';
    $categorySegmentRow = "[data-automation-id='listing_item_{$this->categorySegment->getId()}']";
    $i->see($message, $categorySegmentRow . " [data-colname='Missing plugin message']");
    $productSegmentRow = "[data-automation-id='listing_item_{$this->productSegment->getId()}']";
    $i->see($message, $productSegmentRow . " [data-colname='Missing plugin message']");
    $numberOfOrdersSegmentRow = "[data-automation-id='listing_item_{$this->numberOfOrdersSegment->getId()}']";
    $i->see($message, $numberOfOrdersSegmentRow . " [data-colname='Missing plugin message']");
    $totalSpentSegmentRow = "[data-automation-id='listing_item_{$this->totalSpentSegment->getId()}']";
    $i->see($message, $totalSpentSegmentRow . " [data-colname='Missing plugin message']");
    $customerCountrySegmentRow = "[data-automation-id='listing_item_{$this->customerCountrySegment->getId()}']";
    $i->see($message, $customerCountrySegmentRow . " [data-colname='Missing plugin message']");

    $i->wantTo('Check that Edit links are not clickable');
    $i->assertAttributeContains($categorySegmentRow . ' .mailpoet-listing-actions span.edit_disabled', 'class', 'mailpoet-disabled');
    $i->assertAttributeContains($productSegmentRow . ' .mailpoet-listing-actions span.edit_disabled', 'class', 'mailpoet-disabled');
    $i->assertAttributeContains($numberOfOrdersSegmentRow . ' .mailpoet-listing-actions span.edit_disabled', 'class', 'mailpoet-disabled');
    $i->assertAttributeContains($totalSpentSegmentRow . ' .mailpoet-listing-actions span.edit_disabled', 'class', 'mailpoet-disabled');
    $i->assertAttributeContains($customerCountrySegmentRow . ' .mailpoet-listing-actions span.edit_disabled', 'class', 'mailpoet-disabled');
    $i->seeNoJSErrors();
  }
}
