<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Util\Security;

require_once __DIR__ . '/../DataFactories/Newsletter.php';
require_once __DIR__ . '/../DataFactories/WooCommerceProduct.php';

class EditorProductsCest {

  const EDITOR_PRODUCT_SELECTOR = '.mailpoet_products_container > .mailpoet_block > .mailpoet_container > .mailpoet_block';
  const POST_TITLE = 'Hello World';

  const PRODUCT_NAME = 'Display Settings Product';
  const PRODUCT_DESCRIPTION = 'Full description';
  const PRODUCT_SHORT_DESCRIPTION = 'Short description';

  const KEYWORD_ZERO_RESULTS = '0Non-existent product';
  const KEYWORD_MULTIPLE_RESULTS = '1Multiple products ';

  const PRODUCT_PREFIX_CATEGORY = '2Category product';
  const CATEGORY_ZERO_RESULTS = '3Category with no product';
  const CATEGORY_MULTIPLE_RESULTS = '4Category multiple products';

  const PRODUCTS_COUNT = 2;

  /** @var WooCommerceProduct */
  private $product_factory;

  private $newsletterTitle = 'Editor Products Test';

  private function initializeNewsletter(\AcceptanceTester $I) {
    $this->newsletterTitle = 'Newsletter Title';
    (new Newsletter())
      ->withSubject($this->newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
  }

  private function productsWidgetNotVisible(\AcceptanceTester $I) {
    $I->wantTo('Not see products widget');
    $I->deactivateWooCommerce();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($this->newsletterTitle);
    $I->clickItemRowActionByItemName($this->newsletterTitle, 'Edit');

    $I->waitForText('Spacer');
    $I->waitForElementNotVisible('#automation_editor_block_products');
  }

  private function initializeWooCommerce(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->product_factory = new WooCommerceProduct($I);

    // Create categories
    $this->product_factory->createCategory(self::CATEGORY_ZERO_RESULTS);
    $category_multiple_results_id = $this->product_factory->createCategory(self::CATEGORY_MULTIPLE_RESULTS);

    // Create products for multiple results
    for ($i = 0; $i < self::PRODUCTS_COUNT; $i++) {
      $this->product_factory
        ->withName(self::KEYWORD_MULTIPLE_RESULTS . ' ' . Security::generateRandomString())
        ->create();
      $this->product_factory
        ->withName(self::PRODUCT_PREFIX_CATEGORY . ' ' . Security::generateRandomString())
        ->withCategoryIds([$category_multiple_results_id])
        ->create();
    }

    // Create product for testing display settings
    $this->product_factory
      ->withName(self::PRODUCT_NAME)
      ->withDescription(self::PRODUCT_DESCRIPTION)
      ->withShortDescription(self::PRODUCT_SHORT_DESCRIPTION)
      ->create();

  }

  private function filterProducts(\AcceptanceTester $I) {
    $I->wantTo('Filter products');

    $I->amOnMailpoetPage('Emails');
    $I->waitForText($this->newsletterTitle);
    $I->clickItemRowActionByItemName($this->newsletterTitle, 'Edit');

    // Create products block
    $I->waitForText('Products');
    $I->wait(1); // just to be sure
    $I->dragAndDrop('#automation_editor_block_products', '#mce_0');
    $I->waitForText('PRODUCT SELECTION');

    // Preload tags and categories
    $I->click('.select2-search__field');
    $I->waitForElementNotVisible('.select2-results__option.loading-results');

    // Zero results for category
    $I->selectOptionInSelect2(self::CATEGORY_ZERO_RESULTS);
    $I->waitForText('No products available');
    $this->clearCategories($I);

    // Multiple result for category
    $I->selectOptionInSelect2(self::CATEGORY_MULTIPLE_RESULTS);
    $I->waitForElementNotVisible('.mailpoet_post_scroll_container > div:nth-child(' . (self::PRODUCTS_COUNT + 1) . ')');
    $I->waitForText(self::PRODUCT_PREFIX_CATEGORY, 10, '.mailpoet_post_scroll_container');
    $I->seeNumberOfElements('.mailpoet_post_scroll_container > div', self::PRODUCTS_COUNT);
    $this->clearCategories($I);

    // Click select2 to hide results
    $I->click('.select2-search__field');

    // Zero results for keyword
    $I->fillField('.mailpoet_products_search_term', self::KEYWORD_ZERO_RESULTS);
    $I->waitForText('No products available');

    // Multiple result for keyword
    $I->fillField('.mailpoet_products_search_term', self::KEYWORD_MULTIPLE_RESULTS);
    $I->waitForElementNotVisible('.mailpoet_post_scroll_container > div:nth-child(' . (self::PRODUCTS_COUNT + 1) . ')');
    $I->waitForText(self::KEYWORD_MULTIPLE_RESULTS, 10, '.mailpoet_post_scroll_container');
    $I->seeNumberOfElements('.mailpoet_post_scroll_container > div', self::PRODUCTS_COUNT);

    // Searching for existing post should return zero results
    $I->fillField('.mailpoet_products_search_term', self::POST_TITLE);
    $I->waitForText('No products available');

    // Product is clickable
    $I->fillField('.mailpoet_products_search_term', self::PRODUCT_NAME);
    $I->waitForText(self::PRODUCT_NAME, 10, '.mailpoet_post_scroll_container');
    $I->waitForElementVisible('#mailpoet_select_post_0');
    $I->click('#mailpoet_select_post_0');
    $I->seeCheckboxIsChecked('#mailpoet_select_post_0');
    $I->waitForElement(self::EDITOR_PRODUCT_SELECTOR);
  }

  private function changeDisplaySettings(\AcceptanceTester $I) {
    // Changing display options
    $I->wantTo('Change products settings');
    $I->click('.mailpoet_settings_products_show_display_options');
    $I->waitForElementVisible('.mailpoet_settings_products_show_product_selection');

    // Test "Title Format"
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h1');
    $I->clickLabelWithInput('mailpoet_products_title_format', 'h2');
    $this->waitForChange($I);
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h2');

    // Test "Title Alignment"
    $I->assertAttributeContains(self::EDITOR_PRODUCT_SELECTOR . ' h2', 'style', 'left');
    $I->clickLabelWithInput('mailpoet_products_title_alignment', 'right');
    $this->waitForChange($I);
    $I->assertAttributeContains(self::EDITOR_PRODUCT_SELECTOR . ' h2', 'style', 'right');
  }

  private function clearCategories(\AcceptanceTester $I) {
    $I->click('.select2-selection__clear');
  }

  private function waitForChange(\AcceptanceTester $I) {
    $I->waitForElementNotVisible('.velocity-animating');
    $productClass = $I->grabAttributeFrom(self::EDITOR_PRODUCT_SELECTOR, 'class');
    $I->waitForElementNotVisible('.' . implode('.', explode(' ', $productClass)));
  }

  /**
   * @before initializeNewsletter
   * @before productsWidgetNotVisible
   * @before initializeWooCommerce
   * @before filterProducts
   * @before changeDisplaySettings
   */
  function testProductsWidget(\AcceptanceTester $I) {
    $I->deactivateWooCommerce();
  }

}
