<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Util\Security;

require_once __DIR__ . '/../DataFactories/Newsletter.php';
require_once __DIR__ . '/../DataFactories/WooCommerceProduct.php';

class EditorProductsCest {

  const POST_TITLE = 'Hello World';

  const KEYWORD_ZERO_RESULTS = '0Non-existent product';
  const KEYWORD_MULTIPLE_RESULTS = '1Multiple products ';

  const PRODUCT_PREFIX_CATEGORY = '2Category product';
  const CATEGORY_ZERO_RESULTS = '3Category with no product';
  const CATEGORY_MULTIPLE_RESULTS = '4Category multiple products';

  const PRODUCTS_COUNT = 2;

  /** @var WooCommerceProduct */
  private $product_factory;

  function _before(\AcceptanceTester $I) {
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
  }

  function filterProducts(\AcceptanceTester $I) {
    $I->wantTo('Filter products');

    $newsletterTitle = 'Newsletter Title';
    (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');

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

    // Product is clickable
    $I->click('#mailpoet_select_post_0');
    $I->seeCheckboxIsChecked('#mailpoet_select_post_0');

    // Searching for existing post should return zero results
    $I->fillField('.mailpoet_products_search_term', self::POST_TITLE);
    $I->waitForText('No products available');
  }

  private function clearCategories(\AcceptanceTester $I) {
    $I->click('.select2-selection__clear');
  }

  function _after(\AcceptanceTester $I) {
    $I->deactivateWooCommerce();
  }
}
