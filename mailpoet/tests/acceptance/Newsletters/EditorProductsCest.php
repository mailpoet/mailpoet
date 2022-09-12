<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use MailPoet\Util\Security;

/**
 * @group woo
 */
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
  private $productFactory;

  /** @var NewsletterEntity */
  private $newsletter;

  private function initializeNewsletter(\AcceptanceTester $i) {
    $this->newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
  }

  private function productsWidgetNotVisible(\AcceptanceTester $i) {
    $i->wantTo('Not see products widget');
    $i->deactivateWooCommerce();

    $i->login();
    $i->amEditingNewsletter($this->newsletter->getId());

    $i->waitForElementNotVisible('#automation_editor_block_products');
  }

  private function initializeWooCommerce(\AcceptanceTester $i) {
    $i->activateWooCommerce();
    $this->productFactory = new WooCommerceProduct($i);

    // Create categories
    $this->productFactory->createCategory(self::CATEGORY_ZERO_RESULTS);
    $categoryMultipleResultsId = $this->productFactory->createCategory(self::CATEGORY_MULTIPLE_RESULTS);

    // Create products for multiple results
    for ($index = 0; $index < self::PRODUCTS_COUNT; $index++) {
      $this->productFactory
        ->withName(self::KEYWORD_MULTIPLE_RESULTS . ' ' . Security::generateRandomString())
        ->create();
      $this->productFactory
        ->withName(self::PRODUCT_PREFIX_CATEGORY . ' ' . Security::generateRandomString())
        ->withCategoryIds([$categoryMultipleResultsId])
        ->create();
    }

    // Add product image
    $image = $i->cliToArray(['media', 'import', dirname(__DIR__) . '/../_data/600x400.jpg', '--title="A product picture"', '--porcelain']);
    $imageUrlData = $i->cliToArray(['post', 'get', $image[0], '--field=guid']);
    $imageUrl = $imageUrlData[0];

    // Create products for testing display settings
    $this->productFactory
      ->withName(self::PRODUCT_NAME . ' 2')
      ->withDescription(self::PRODUCT_DESCRIPTION . ' 2')
      ->withShortDescription(self::PRODUCT_SHORT_DESCRIPTION . ' 2')
      ->withImages([$imageUrl])
      ->create();
    $this->productFactory
      ->withName(self::PRODUCT_NAME)
      ->withDescription(self::PRODUCT_DESCRIPTION)
      ->withShortDescription(self::PRODUCT_SHORT_DESCRIPTION)
      ->withImages([$imageUrl])
      ->create();

  }

  private function filterProducts(\AcceptanceTester $i) {
    $i->wantTo('Filter products');
    $i->amEditingNewsletter($this->newsletter->getId());

    // Create products block (added wait checks to avoid flakiness)
    $i->waitForText('Content');
    $i->scrollTo('[data-automation-id="newsletter_title"]');
    $i->dragAndDrop('#automation_editor_block_products', '#mce_0');
    $i->waitForText('There is no content to display.');
    $i->waitForText('Display options');

    // Preload tags and categories
    $i->click('.select2-search__field');
    $i->waitForElementNotVisible('.select2-results__option.loading-results');

    $i->wantTo('Select category without products');
    $i->selectOptionInSelect2(self::CATEGORY_ZERO_RESULTS);
    $i->waitForText('No products available');
    $this->clearCategories($i);

    $i->wantTo('Select category with multiple products');
    // Try twice since it may probably take longer to load from
    $i->selectOptionInSelect2(self::CATEGORY_MULTIPLE_RESULTS);
    try {
      $i->seeSelectedInSelect2(self::CATEGORY_MULTIPLE_RESULTS);
      $this->checkElements($i);
    } catch (\Exception $e) {
      $i->selectOptionInSelect2(self::CATEGORY_MULTIPLE_RESULTS);
      $i->seeSelectedInSelect2(self::CATEGORY_MULTIPLE_RESULTS);
      $this->checkElements($i);
    }
    $this->clearCategories($i);

    // Click select2 to hide results
    $i->click('.select2-search__field');

    // Zero results for keyword
    $i->fillField('.mailpoet_products_search_term', self::KEYWORD_ZERO_RESULTS);
    $i->waitForText('No products available');

    // Multiple result for keyword
    $i->fillField('.mailpoet_products_search_term', self::KEYWORD_MULTIPLE_RESULTS);
    $i->waitForElementNotVisible('.mailpoet_products_scroll_container > div:nth-child(' . (self::PRODUCTS_COUNT + 1) . ')');
    $i->waitForText(self::KEYWORD_MULTIPLE_RESULTS, 15, '.mailpoet_products_scroll_container');
    $i->seeNumberOfElements('.mailpoet_products_scroll_container > div', self::PRODUCTS_COUNT);

    // Searching for existing post should return zero results
    $i->fillField('.mailpoet_products_search_term', self::POST_TITLE);
    $i->waitForText('No products available');

    // Product is clickable
    $i->fillField('.mailpoet_products_search_term', self::PRODUCT_NAME);
    $i->waitForText(self::PRODUCT_NAME, 15, '.mailpoet_products_scroll_container');
    $i->waitForElementVisible('#mailpoet_select_product_0');
    $i->click('#mailpoet_select_product_0');
    $i->seeCheckboxIsChecked('#mailpoet_select_product_0');
    $i->click('#mailpoet_select_product_1');
    $i->waitForElement(self::EDITOR_PRODUCT_SELECTOR);
  }

  private function changeDisplaySettings(\AcceptanceTester $i) {
    // Changing display options
    $i->wantTo('Change products settings');
    $i->click('.mailpoet_settings_products_show_display_options');
    $i->waitForElementVisible('.mailpoet_settings_products_show_product_selection');
    $i->wait(0.35); // Animation

    // Test "Display Type"
    $i->see(self::PRODUCT_SHORT_DESCRIPTION, self::EDITOR_PRODUCT_SELECTOR);
    $i->seeElement('.mailpoet_products_title_position');
    $i->clickLabelWithInput('mailpoet_products_display_type', 'titleOnly');
    $this->waitForChange($i);
    $i->dontSeeElement(self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post');
    $i->dontSeeElement('.mailpoet_products_title_position');
    $i->clickLabelWithInput('mailpoet_products_display_type', 'full');
    $this->waitForChange($i);
    $i->see(self::PRODUCT_DESCRIPTION, self::EDITOR_PRODUCT_SELECTOR);

    // Test "Title Format"
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h1');
    $i->clickLabelWithInput('mailpoet_products_title_format', 'h2');
    $this->waitForChange($i);
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h2');

    // Test "Title Alignment"
    $i->assertAttributeContains(self::EDITOR_PRODUCT_SELECTOR . ' h2', 'style', 'left');
    $i->clickLabelWithInput('mailpoet_products_title_alignment', 'right');
    $this->waitForChange($i);
    $i->assertAttributeContains(self::EDITOR_PRODUCT_SELECTOR . ' h2', 'style', 'right');

    // Test "Title as a Link"
    $i->dontSeeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h2 a');
    $i->clickLabelWithInput('mailpoet_products_title_as_links', 'true');
    $this->waitForChange($i);
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' h2 a');

    // Test "Price"
    $i->clickLabelWithInput('mailpoet_products_price_position', 'above');
    $this->waitForChange($i);
    $i->seeElementInDOM(self::PRICE_XPATH . '/following::*[name()="p"][@class="mailpoet_wp_post"]');
    $i->clickLabelWithInput('mailpoet_products_price_position', 'below');
    $this->waitForChange($i);
    $i->seeElementInDOM(self::PRICE_XPATH . '/preceding::*[name()="p"][@class="mailpoet_wp_post"]');
    $i->clickLabelWithInput('mailpoet_products_price_position', 'hidden');
    $this->waitForChange($i);
    $i->dontSeeElementInDOM(self::PRICE_XPATH);

    // Test "Buy now" button
    $i->see('Buy now', self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $i->fillField('.mailpoet_products_read_more_text', 'Go Shopping');
    $this->waitForChange($i);
    $i->dontSee('Buy now', self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $i->see('Go Shopping', self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $i->clickLabelWithInput('mailpoet_products_read_more_type', 'button');
    $this->waitForChange($i);
    $i->dontSeeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_wp_post + p');
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ' .mailpoet_editor_button');

    // Test "Divider"
    $i->seeElementInDOM(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider_block');
    $i->assertAttributeContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider', 'style', '3px');
    $i->click('.mailpoet_products_select_divider');
    $i->fillField('.mailpoet_field_divider_border_width_input', 10);
    $this->waitForChange($i);
    $i->click('.mailpoet_done_editing');
    $i->assertAttributeContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider', 'style', '10px');
    $i->clickLabelWithInput('mailpoet_products_show_divider', 'false');
    $this->waitForChange($i);
    $i->dontSeeElementInDOM(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_divider_block');

    // Test "Image width"
    $i->assertAttributeNotContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_image_block', 'class', 'mailpoet_full_image');
    $i->clickLabelWithInput('imageFullWidth', 'true');
    $this->waitForChange($i);
    $i->assertAttributeContains(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_image_block', 'class', 'mailpoet_full_image');

    // Test "Image position"
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(2) .mailpoet_block:nth-child(2) .mailpoet_image_block');
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(4) .mailpoet_block:nth-child(1) .mailpoet_image_block');
    $i->clickLabelWithInput('mailpoet_products_featured_image_position', 'none');
    $this->waitForChange($i);
    $i->dontSeeElementInDOM(self::EDITOR_PRODUCTS_SELECTOR . ' .mailpoet_image_block');
    $i->clickLabelWithInput('mailpoet_products_featured_image_position', 'left');
    $this->waitForChange($i);
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(2) .mailpoet_block:nth-child(1) .mailpoet_image_block');
    $i->clickLabelWithInput('mailpoet_products_featured_image_position', 'right');
    $this->waitForChange($i);
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(2) .mailpoet_block:nth-child(2) .mailpoet_image_block');
    $i->clickLabelWithInput('mailpoet_products_featured_image_position', 'centered');
    $this->waitForChange($i);
    $i->seeElementInDOM(self::EDITOR_PRODUCT_SELECTOR . ':nth-child(1) .mailpoet_image_block');
  }

  private function clearCategories(\AcceptanceTester $i) {
    $i->click('.select2-selection__clear');
  }

  private function waitForChange(\AcceptanceTester $i) {
    $productClass = $i->grabAttributeFrom(self::EDITOR_PRODUCT_SELECTOR, 'class');
    $i->waitForElementNotVisible('.' . implode('.', explode(' ', $productClass)));
    $i->waitForElementVisible(self::EDITOR_PRODUCT_SELECTOR);
    $i->waitForElementNotVisible('.velocity-animating');
  }

  private function checkElements(\AcceptanceTester $i) {
    $i->waitForElementNotVisible('.mailpoet_products_scroll_container > div:nth-child(' . (self::PRODUCTS_COUNT + 1) . ')');
    $i->waitForText(self::PRODUCT_PREFIX_CATEGORY, 15, '.mailpoet_products_scroll_container');
    $i->seeNumberOfElements('.mailpoet_products_scroll_container > div', self::PRODUCTS_COUNT);
  }

  public function testProductsWidget(\AcceptanceTester $i) {
    $this->initializeNewsletter($i);
    $this->productsWidgetNotVisible($i);
    $this->initializeWooCommerce($i);
    $this->filterProducts($i);
    $this->changeDisplaySettings($i);
  }
}
