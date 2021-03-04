<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\WooCommerceProduct;

class WooCommerceDynamicSegmentsCest {
  const CATEGORY_SEGMENT = 'Purchase in category segment';
  const PRODUCT_SEGMENT = 'Purchased product segment';

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
  }

  public function addCustomerToWooCommerceSegments(\AcceptanceTester $i) {
    $i->wantTo('Check if customer who registers is added to WooCommerce dynamic segments');
    $customerEmail = 'customer_1@example.com';
    $i->orderProduct($this->productInCategory, $customerEmail);

    $i->login();
    $i->wantTo('Check subscriber is in category segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::CATEGORY_SEGMENT);
    $categorySegmentRow = "[data-automation-id='listing_item_{$this->categorySegment->getId()}']";
    $i->see('1', $categorySegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::CATEGORY_SEGMENT, 'View Subscribers');
    $i->waitForText($customerEmail);

    $i->wantTo('Check subscriber is in product segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::PRODUCT_SEGMENT);
    $productSegmentRow = "[data-automation-id='listing_item_{$this->productSegment->getId()}']";
    $i->see('1', $productSegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::PRODUCT_SEGMENT, 'View Subscribers');
    $i->waitForText($customerEmail);
  }

  public function addCustomerOnlyToCategorySegment(\AcceptanceTester $i) {
    $i->wantTo('Check if customer who registers is added to WooCommerce category and not to product segment');
    $customerEmail = 'customer_2@example.com';
    $differentProductWithCategory = $this->productFactory->withCategoryIds([$this->productCategoryId])->create();
    $i->orderProduct($differentProductWithCategory, $customerEmail);

    $i->login();
    $i->wantTo('Check subscriber is in category segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::CATEGORY_SEGMENT);
    $categorySegmentRow = "[data-automation-id='listing_item_{$this->categorySegment->getId()}']";
    $i->see('1', $categorySegmentRow . " [data-colname='Number of subscribers']");
    $i->clickItemRowActionByItemName(self::CATEGORY_SEGMENT, 'View Subscribers');
    $i->waitForText($customerEmail);

    $i->wantTo('Check subscriber is in product segment');
    $i->amOnMailpoetPage('Lists');
    $i->click('[data-automation-id="dynamic-segments-tab"]');
    $i->waitForText(self::PRODUCT_SEGMENT);
    $productSegmentRow = "[data-automation-id='listing_item_{$this->productSegment->getId()}']";
    $i->see('0', $productSegmentRow . " [data-colname='Number of subscribers']");
  }
}
