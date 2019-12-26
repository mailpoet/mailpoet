<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Util\Security;

class EditorProductsCest {

  const EDITOR_PRODUCTS_SELECTOR = '.mailpoet_products_container > .mailpoet_block > .mailpoet_container';
  const EDITOR_PRODUCT_SELECTOR = '.mailpoet_products_container > .mailpoet_block > .mailpoet_container > .mailpoet_block';
  const PRICE_XPATH = '//*[name()="h2"][.//*[name()="span"][contains(@class, "woocommerce-Price-amount")]]';
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

  /** @var NewsletterModel */
  private $newsletter;

  private function initializeNewsletter(\AcceptanceTester $I) {
    $this->newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
  }

  private function productsWidgetNotVisible(\AcceptanceTester $I) {
    $I->wantTo('Not see products widget');
    $I->deactivateWooCommerce();

    $I->login();
    $I->amEditingNewsletter($this->newsletter->id);

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

    // Create products for testing display settings
    $this->product_factory
      ->withName(self::PRODUCT_NAME . ' 2')
      ->withDescription(self::PRODUCT_DESCRIPTION . ' 2')
      ->withShortDescription(self::PRODUCT_SHORT_DESCRIPTION . ' 2')
      ->withImages(['https://via.placeholder.com/600x400.jpg'])
      ->create();
    $this->product_factory
      ->withName(self::PRODUCT_NAME)
      ->withDescription(self::PRODUCT_DESCRIPTION)
      ->withShortDescription(self::PRODUCT_SHORT_DESCRIPTION)
      ->withImages(['https://via.placeholder.com/600x400.jpg'])
      ->create();

  }

  private function filterProducts(\AcceptanceTester $I) {
    $I->wantTo('Filter products');
    $I->amEditingNewsletter($this->newsletter->id);

    // Create products block
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
    $I->waitForElementNotVisible('.mailpoet_products_scroll_container > div:nth-child(' . (self::PRODUCTS_COUNT + 1) . ')');
    $I->waitForText(self::PRODUCT_PREFIX_CATEGORY, 10, '.mailpoet_products_scroll_container');
    $I->seeNumberOfElements('.mailpoet_products_scroll_container > div', self::PRODUCTS_COUNT);
    $this->clearCategories($I);

    // Click select2 to hide results
    $I->click('.select2-search__field');

    // Zero results for keyword
    $I->fillField('.mailpoet_products_search_term', self::KEYWORD_ZERO_RESULTS);
    $I->waitForText('No products available');

    // Multiple result for keyword
    $I->fillField('.mailpoet_products_search_term', self::KEYWORD_MULTIPLE_RESULTS);
    $I->waitForElementNotVisible('.mailpoet_products_scroll_container > div:nth-child(' . (self::PRODUCTS_COUNT + 1) . ')');
    $I->waitForText(self::KEYWORD_MULTIPLE_RESULTS, 10, '.mailpoet_products_scroll_container');
    $I->seeNumberOfElements('.mailpoet_products_scroll_container > div', self::PRODUCTS_COUNT);

    // Searching for existing post should return zero results
    $I->fillField('.mailpoet_products_search_term', self::POST_TITLE);
    $I->waitForText('No products available');

    // Product is clickable
    $I->fillField('.mailpoet_products_search_term', self::PRODUCT_NAME);
    $I->waitForText(self::PRODUCT_NAME, 10, '.mailpoet_products_scroll_container');
    $I->waitForElementVisible('#mailpoet_select_product_0');
    $I->click('#mailpoet_select_product_0');
    $I->seeCheckboxIsChecked('#mailpoet_select_product_0');
    $I->click('#mailpoet_select_product_1');
    $I->waitForElement(self::EDITOR_PRODUCT_SELECTOR);
  }

  private function changeDisplaySettings(\AcceptanceTester $I) {
    // Changing display options
    $I->wantTo('Change products settings');
    $I->click('.mailpoet_settings_products_show_display_options');
    $I->waitForElementVisible('.mailpoet_settings_products_show_product_selection');
    $I->wait(0.35); // Animation

    // Test "Display Type"
    $I->see(self::PRODUCT_SHORT_DESCRIPTION, self::EDITOR_PRODUCT_SELECTOR);
    $I->seeElement('.mailpoet_products_title_position');
    $I->clickLabelWithInput('mailpoet_products_display_type', 'titleOnly');
    $this->waitForChange($I);
    $I->dontSeeElement(self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post');
    $I->dontSeeElement('.mailpoet_products_title_position');
    $I->clickLabelWithInput('mailpoet_products_display_type', 'full');
    $this->waitForChange($I);
    $I->see(self::PRODUCT_DESCRIPTION, self::EDITOR_PRODUCT_SELECTOR);

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

    // Test "Title as a Link"
    $I->dontSeeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h2 a');
    $I->clickLabelWithInput('mailpoet_products_title_as_links', 'true');
    $this->waitForChange($I);
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h2 a');

    // Test "Price"
    $I->clickLabelWithInput('mailpoet_products_price_position', 'above');
    $this->waitForChange($I);
    $I->seeElementInDOM(self::PRICE_XPATH . '/following::*[name()="p"][@class="mailpoet_wp_post"]');
    $I->clickLabelWithInput('mailpoet_products_price_position', 'below');
    $this->waitForChange($I);
    $I->seeElementInDOM(self::PRICE_XPATH . '/preceding::*[name()="p"][@class="mailpoet_wp_post"]');
    $I->clickLabelWithInput('mailpoet_products_price_position', 'hidden');
    $this->waitForChange($I);
    $I->dontSeeElementInDOM(self::PRICE_XPATH);

    // Test "Buy now" button
    $I->see('Buy now', self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $I->fillField('.mailpoet_products_read_more_text', 'Go Shopping');
    $this->waitForChange($I);
    $I->dontSee('Buy now', self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $I->see('Go Shopping', self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $I->clickLabelWithInput('mailpoet_products_read_more_type', 'button');
    $this->waitForChange($I);
    $I->dontSeeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_editor_button');

    // Test "Divider"
    $I->seeElementInDOM(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider_block');
    $I->assertAttributeContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider', 'style', '3px');
    $I->click('.mailpoet_products_select_divider');
    $I->fillField('.mailpoet_field_divider_border_width_input', 10);
    $this->waitForChange($I);
    $I->click('.mailpoet_done_editing');
    $I->assertAttributeContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider', 'style', '10px');
    $I->clickLabelWithInput('mailpoet_products_show_divider', 'false');
    $this->waitForChange($I);
    $I->dontSeeElementInDOM(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider_block');

    // Test "Image width"
    $I->assertAttributeNotContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_image_block', 'class', 'mailpoet_full_image');
    $I->clickLabelWithInput('imageFullWidth', 'true');
    $this->waitForChange($I);
    $I->assertAttributeContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_image_block', 'class', 'mailpoet_full_image');

    // Test "Image position"
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(2) .mailpoet_block:nth-child(2) .mailpoet_image_block');
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(4) .mailpoet_block:nth-child(1) .mailpoet_image_block');
    $I->clickLabelWithInput('mailpoet_products_featured_image_position', 'none');
    $this->waitForChange($I);
    $I->dontSeeElementInDOM(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_image_block');
    $I->clickLabelWithInput('mailpoet_products_featured_image_position', 'left');
    $this->waitForChange($I);
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(2) .mailpoet_block:nth-child(1) .mailpoet_image_block');
    $I->clickLabelWithInput('mailpoet_products_featured_image_position', 'right');
    $this->waitForChange($I);
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(2) .mailpoet_block:nth-child(2) .mailpoet_image_block');
    $I->clickLabelWithInput('mailpoet_products_featured_image_position', 'centered');
    $this->waitForChange($I);
    $I->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(1) .mailpoet_image_block');
  }

  private function clearCategories(\AcceptanceTester $I) {
    $I->click('.select2-selection__clear');
  }

  private function waitForChange(\AcceptanceTester $I) {
    $productClass = $I->grabAttributeFrom(self::EDITOR_PRODUCT_SELECTOR, 'class');
    $I->waitForElementNotVisible('.' . implode('.', explode(' ', $productClass)));
    $I->waitForElementVisible(self::EDITOR_PRODUCT_SELECTOR);
    $I->waitForElementNotVisible('.velocity-animating');
  }

  public function testProductsWidget(\AcceptanceTester $I) {
    $this->initializeNewsletter($I);
    $this->productsWidgetNotVisible($I);
    $this->initializeWooCommerce($I);
    $this->filterProducts($I);
    $this->changeDisplaySettings($I);
  }

}
