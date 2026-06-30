<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
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

    $i->wantTo('Check subscriber is in category segment');
    $this->seeSubscribersCountInSegment($i, $this->categorySegment, '2');
    $this->clickAction($i, $this->categorySegment, 'View subscribers');
    $i->waitForText($customerEmail);

    $i->wantTo('Check subscriber is in product segment');
    $this->seeSubscribersCountInSegment($i, $this->productSegment, '2');
    $this->clickAction($i, $this->productSegment, 'View subscribers');
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

    $i->wantTo('Check subscriber is in category segment');
    $this->seeSubscribersCountInSegment($i, $this->categorySegment, '2');
    $this->clickAction($i, $this->categorySegment, 'View subscribers');
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

    $i->wantTo('Check there is one subscriber in the number of orders segments (the segment was configured to match customers that placed one order in the last day)');
    $this->seeSubscribersCountInSegment($i, $this->numberOfOrdersSegment, '1');
    $this->clickAction($i, $this->numberOfOrdersSegment, 'View subscribers');
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

    $i->wantTo('Check that there is one subscriber in the single order value segment');
    $this->seeSubscribersCountInSegment($i, $this->singleOrderValueSegment, '1');
    $this->clickAction($i, $this->singleOrderValueSegment, 'View subscribers');
    $i->waitForText($customerEmail2);
    $i->dontSee($customerEmail1);
  }

  public function checkThatCustomersAreAddedToTotalSpentSegment(\AcceptanceTester $i) {
    $i->wantTo('Check that customers are added to the total spent segment when the value of orders they placed matches what is expected');
    $customerEmail = 'customer_2@example.com';
    $anyProduct = $this->productInCategory;
    $i->orderProduct($anyProduct, $customerEmail);

    $i->login();

    $i->wantTo('Check that there is one subscriber in the total spent segment');
    $this->seeSubscribersCountInSegment($i, $this->totalSpentSegment, '1');
    $this->clickAction($i, $this->totalSpentSegment, 'View subscribers');
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

    $i->wantTo('Check that there is one subscriber in customer country segment');
    $this->seeSubscribersCountInSegment($i, $this->customerCountrySegment, '2');
    $this->clickAction($i, $this->customerCountrySegment, 'View subscribers');
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
    $categorySegmentRow = "[data-automation-id='mailpoet_dynamic_segment_plugin_missing_message_{$this->categorySegment->getId()}']";
    $i->see($message, $categorySegmentRow);
    $productSegmentRow = "[data-automation-id='mailpoet_dynamic_segment_plugin_missing_message_{$this->productSegment->getId()}']";
    $i->see($message, $productSegmentRow);
    $numberOfOrdersSegmentRow = "[data-automation-id='mailpoet_dynamic_segment_plugin_missing_message_{$this->numberOfOrdersSegment->getId()}']";
    $i->see($message, $numberOfOrdersSegmentRow);
    $totalSpentSegmentRow = "[data-automation-id='mailpoet_dynamic_segment_plugin_missing_message_{$this->totalSpentSegment->getId()}']";
    $i->see($message, $totalSpentSegmentRow);
    $customerCountrySegmentRow = "[data-automation-id='mailpoet_dynamic_segment_plugin_missing_message_{$this->customerCountrySegment->getId()}']";
    $i->see($message, $customerCountrySegmentRow);

    $i->wantTo('Check that Edit links are not clickable');
    $this->seeDisabledEditAction($i, $this->categorySegment);
    $this->seeDisabledEditAction($i, $this->productSegment);
    $this->seeDisabledEditAction($i, $this->numberOfOrdersSegment);
    $this->seeDisabledEditAction($i, $this->totalSpentSegment);
    $this->seeDisabledEditAction($i, $this->customerCountrySegment);
    $i->seeNoJSErrors();
  }

  /**
   * Assert a WooCommerce dynamic segment shows the expected "all" subscriber count.
   *
   * These counts come from WooCommerce's analytics lookup tables, which are filled
   * by an asynchronous Action Scheduler batch import, not by the legacy per-item
   * wc-admin_import_* actions. The listing reads the count once on load with no
   * polling, so asserting after a single fixed wait races the import and sees 0
   * before it finishes; Action Scheduler also claims only one batch per run, so the
   * chain needs several runs to drain.
   *
   * Run the full Action Scheduler queue and reload the listing a few times until
   * the count settles.
   */
  private function seeSubscribersCountInSegment(\AcceptanceTester $i, SegmentEntity $segment, string $expectedCount): void {
    $countElement = "[data-automation-id='mailpoet_dynamic_segment_count_all_{$segment->getId()}']";
    $maxAttempts = 5;
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      $i->cli(['action-scheduler', 'run', '--force']);
      $i->amOnMailpoetPage('Segments');
      $i->waitForText($segment->getName());
      try {
        $i->waitForText($expectedCount, 5, $countElement);
        return;
      } catch (\Exception $e) {
        if ($attempt === $maxAttempts) {
          throw $e;
        }
        $i->wait(2);
      }
    }
  }

  private function clickAction(\AcceptanceTester $i, SegmentEntity $segmentEntity, $actionName) {
    if ($actionName === 'View subscribers') {
      $i->amOnPage(
        '/wp-admin/admin.php?page=mailpoet-subscribers#/filter[segment=' . $segmentEntity->getId() . ']'
      );
      return;
    }

    $i->clickWooTableActionByItemName($segmentEntity->getName(), $actionName);
  }

  private function seeDisabledEditAction(\AcceptanceTester $i, SegmentEntity $segmentEntity): void {
    $i->clickWooTableMoreButtonByItemName($segmentEntity->getName());
    $menu = ['xpath' => '//*[@role="menu"]'];
    $i->waitForElementVisible($menu);
    // DataViews renders disabled actions as regular menu items without
    // aria-disabled; verify Edit is replaced by the unavailable label.
    $i->see('Edit unavailable', $menu);
    $i->dontSee('Edit', ['xpath' => '//*[@role="menu"]//*[@role="menuitem"][normalize-space(.)="Edit"]']);
    $i->pressKey('body', WebDriverKeys::ESCAPE);
  }
}
