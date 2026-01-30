<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use MailPoet\Util\CdnAssetUrl;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class PatternsControllerTest extends \MailPoetTest {
  public function _before(): void {
    parent::_before();
    $this->cleanupPatterns();
    $this->cleanupPatternCategories();
  }

  public function testItRegistersAllPatternsWhenWooCommerceIsActive(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $patternNames = array_column($blockPatterns, 'name');

    // Non-WooCommerce patterns
    $this->assertContains('mailpoet/newsletter-content', $patternNames);
    $this->assertContains('mailpoet/sale-announcement', $patternNames);
    $this->assertContains('mailpoet/new-products-announcement', $patternNames);
    $this->assertContains('mailpoet/educational-campaign', $patternNames);
    $this->assertContains('mailpoet/event-invitation', $patternNames);
    $this->assertContains('mailpoet/product-restock-notification', $patternNames);
    $this->assertContains('mailpoet/new-arrivals-announcement', $patternNames);
    $this->assertContains('mailpoet/welcome-email-content', $patternNames);

    // WooCommerce-dependent patterns (uses coupon codes, product blocks, or purchase/abandoned-cart categories)
    $this->assertContains('mailpoet/welcome-with-discount-email-content', $patternNames);
    $this->assertContains('mailpoet/first-purchase-thank-you', $patternNames);
    $this->assertContains('mailpoet/post-purchase-thank-you', $patternNames);
    $this->assertContains('mailpoet/product-purchase-follow-up', $patternNames);
    $this->assertContains('mailpoet/win-back-customer', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-content', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-with-discount-content', $patternNames);

    // Verify total count
    $this->assertCount(15, $blockPatterns);
  }

  public function testItRegistersAllCategoriesWhenWooCommerceIsActive(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $registry = \WP_Block_Pattern_Categories_Registry::get_instance();

    $newsletterCategory = $registry->get_registered('newsletter');
    $this->assertIsArray($newsletterCategory);
    $this->assertEquals('newsletter', $newsletterCategory['name']);
    $this->assertNotEmpty($newsletterCategory['label']);

    $welcomeCategory = $registry->get_registered('welcome');
    $this->assertIsArray($welcomeCategory);
    $this->assertEquals('welcome', $welcomeCategory['name']);
    $this->assertNotEmpty($welcomeCategory['label']);

    $purchaseCategory = $registry->get_registered('purchase');
    $this->assertIsArray($purchaseCategory);
    $this->assertEquals('purchase', $purchaseCategory['name']);
    $this->assertNotEmpty($purchaseCategory['label']);

    $abandonedCartCategory = $registry->get_registered('abandoned-cart');
    $this->assertIsArray($abandonedCartCategory);
    $this->assertEquals('abandoned-cart', $abandonedCartCategory['name']);
    $this->assertNotEmpty($abandonedCartCategory['label']);
  }

  public function testItDoesNotRegisterWooCommercePatternsWhenWooCommerceIsInactive(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(false);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $patternNames = array_column($blockPatterns, 'name');

    // Should include non-WooCommerce patterns
    $this->assertContains('mailpoet/newsletter-content', $patternNames);
    $this->assertContains('mailpoet/sale-announcement', $patternNames);
    $this->assertContains('mailpoet/welcome-email-content', $patternNames);

    // Should NOT include WooCommerce-dependent patterns
    $this->assertNotContains('mailpoet/welcome-with-discount-email-content', $patternNames);
    $this->assertNotContains('mailpoet/first-purchase-thank-you', $patternNames);
    $this->assertNotContains('mailpoet/post-purchase-thank-you', $patternNames);
    $this->assertNotContains('mailpoet/product-purchase-follow-up', $patternNames);
    $this->assertNotContains('mailpoet/win-back-customer', $patternNames);
    $this->assertNotContains('mailpoet/abandoned-cart-content', $patternNames);
    $this->assertNotContains('mailpoet/abandoned-cart-with-discount-content', $patternNames);

    // Verify total count (only non-WooCommerce patterns)
    $this->assertCount(8, $blockPatterns);
  }

  public function testItDoesNotRegisterWooCommerceCategoriesWhenWooCommerceIsInactive(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(false);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $registry = \WP_Block_Pattern_Categories_Registry::get_instance();

    // Should include non-WooCommerce categories
    $newsletterCategory = $registry->get_registered('newsletter');
    $this->assertIsArray($newsletterCategory);

    $welcomeCategory = $registry->get_registered('welcome');
    $this->assertIsArray($welcomeCategory);

    // Should NOT include WooCommerce-dependent categories
    $purchaseCategory = $registry->get_registered('purchase');
    $this->assertNull($purchaseCategory);

    $abandonedCartCategory = $registry->get_registered('abandoned-cart');
    $this->assertNull($abandonedCartCategory);
  }

  private function cleanupPatterns(): void {
    $registry = \WP_Block_Patterns_Registry::get_instance();
    $blockPatterns = $registry->get_all_registered();
    foreach ($blockPatterns as $pattern) {
      $registry->unregister($pattern['name']);
    }
  }

  private function cleanupPatternCategories(): void {
    $registry = \WP_Block_Pattern_Categories_Registry::get_instance();
    $categories = $registry->get_all_registered();
    foreach ($categories as $category) {
      $registry->unregister($category['name']);
    }
  }
}
