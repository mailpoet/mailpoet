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
  const SINGLE_ORDER_VALUE_SEGMENT = 'Single order value segment';
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
  private $singleOrderValueSegment;

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
    $this->singleOrderValueSegment = $segmentFactory
      ->withName(self::SINGLE_ORDER_VALUE_SEGMENT)
      ->withWooCommerceSingleOrderValueFilter()
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
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::CATEGORY_SEGMENT);
    $categorySegmentSubscribedElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->categorySegment->getId()}']";
    $i->see('2', $categorySegmentSubscribedElement);
    $this->clickAction($i, $this->categorySegment, 'View Subscribers');
    $i->waitForText($customerEmail);

    $i->wantTo('Check subscriber is in product segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::PRODUCT_SEGMENT);
    $productSegmentSubscribedElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->productSegment->getId()}']";
    $i->see('2', $productSegmentSubscribedElement);
    $this->clickAction($i, $this->productSegment, 'View Subscribers');
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
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::CATEGORY_SEGMENT);
    $categorySegmentSubscribedElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->categorySegment->getId()}']";

    $i->see('2', $categorySegmentSubscribedElement);
    $this->clickAction($i, $this->categorySegment, 'View Subscribers');
    $i->waitForText($customerEmail);
    $i->waitForText($guestEmail);

    $i->wantTo('Check subscriber is in product segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::PRODUCT_SEGMENT);
    $productSegmentSubscribedElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->productSegment->getId()}']";

    $i->see('0', $productSegmentSubscribedElement);
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
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::NUMBER_OF_ORDERS_SEGMENT);
    $numberOfOrdersSegmentSubscribedElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->numberOfOrdersSegment->getId()}']";

    $i->see('1', $numberOfOrdersSegmentSubscribedElement);
    $this->clickAction($i, $this->numberOfOrdersSegment, 'View Subscribers');
    $i->waitForText($customer1Email);
  }

  public function checkThatCustomersAreAddedToSingleOrderValueSegment(\AcceptanceTester $i) {
    $i->wantTo('Check that customers are added to the single order value segment when the value of at least one order they placed matches what is expected');
    $customerEmail1 = 'customer_1@example.com';
    $anyProduct = $this->productInCategory;
    $i->orderProduct($anyProduct, $customerEmail1);

    $customerEmail2 = 'customer_2@example.com';
    $anotherProduct = $this->productFactory->withPrice(20)->create();
    $i->orderProduct($anotherProduct, $customerEmail2);

    $i->login();

    // run action scheduler to sync customer and order data to lookup tables
    $i->wait(2);
    $i->cli(['action-scheduler', 'run', '--hooks=wc-admin_import_orders,wc-admin_import_customers --force']);

    $i->wantTo('Check that there is one subscriber in the single order value segment');
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::SINGLE_ORDER_VALUE_SEGMENT);
    $singleOrderValueSegmentSubscribedElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->singleOrderValueSegment->getId()}']";

    $i->see('1', $singleOrderValueSegmentSubscribedElement);
    $this->clickAction($i, $this->singleOrderValueSegment, 'View Subscribers');
    $i->waitForText($customerEmail2);
    $i->dontSee($customerEmail1);
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
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::TOTAL_SPENT_SEGMENT);
    $totalSpentSegmentSubscribedElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->totalSpentSegment->getId()}']";

    $i->see('1', $totalSpentSegmentSubscribedElement);
    $this->clickAction($i, $this->totalSpentSegment, 'View Subscribers');
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
    $i->amOnMailpoetPage('Segments');
    $i->waitForText(self::CUSTOMER_IN_COUNTRY);
    $customerInCountryCountElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$this->customerCountrySegment->getId()}']";
    $i->see('2', $customerInCountryCountElement);
    $this->clickAction($i, $this->customerCountrySegment, 'View Subscribers');
    $i->waitForText($customerEmail);
    $i->waitForText($guestEmail);
  }

  public function displayMessageWhenPluginIsDeactivated(\AcceptanceTester $i) {
    $i->wantTo('Check if count of subscribers is hidden and message with plugin name is visible');
    $i->deactivateWooCommerce();
    $i->login();
    $i->wantTo('Check messages in list when WooCommerce is deactivated');
    $i->amOnMailpoetPage('Segments');

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

  private function clickAction(\AcceptanceTester $i, SegmentEntity $segmentEntity, $actionName) {
    $column = sprintf('[data-automation-id="mailpoet_dynamic_segment_actions_%d"]', $segmentEntity->getId());

    switch ($actionName) {
      case 'View Subscribers':
        $actionName = 'a';
        break;
      case 'Edit':
        $actionName = 'a:nth-child(2)';
        break;
    }
    $i->click($actionName, $column);
  }
}
