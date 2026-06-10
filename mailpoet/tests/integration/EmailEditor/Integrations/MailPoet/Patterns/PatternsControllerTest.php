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
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn('10.8.0');
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

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

    // WooCommerce-dependent patterns (uses product blocks or purchase/abandoned-cart categories)
    $this->assertContains('mailpoet/first-purchase-thank-you', $patternNames);
    $this->assertContains('mailpoet/post-purchase-thank-you', $patternNames);
    $this->assertContains('mailpoet/product-purchase-follow-up', $patternNames);
    $this->assertContains('mailpoet/tag-purchase-follow-up', $patternNames);
    $this->assertContains('mailpoet/category-purchase-follow-up', $patternNames);
    $this->assertContains('mailpoet/ask-for-review-post-purchase', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-content', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-reminder-content', $patternNames);

    // WooCommerce 10.8.0+ patterns (uses generated coupon block)
    $this->assertContains('mailpoet/welcome-with-discount-email-content', $patternNames);
    $this->assertContains('mailpoet/win-back-customer', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-with-discount-content', $patternNames);

    // Verify total count
    $this->assertCount(19, $blockPatterns);
  }

  public function testItRegistersAllCategoriesWhenWooCommerceIsActive(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $registry = \WP_Block_Pattern_Categories_Registry::get_instance();

    $salesAnnouncementCategory = $registry->get_registered('sales-announcement');
    $this->assertIsArray($salesAnnouncementCategory);
    $this->assertEquals('sales-announcement', $salesAnnouncementCategory['name']);
    $this->assertNotEmpty($salesAnnouncementCategory['label']);

    $educationalCampaignCategory = $registry->get_registered('educational-campaign');
    $this->assertIsArray($educationalCampaignCategory);
    $this->assertEquals('educational-campaign', $educationalCampaignCategory['name']);
    $this->assertNotEmpty($educationalCampaignCategory['label']);

    $eventCategory = $registry->get_registered('event');
    $this->assertIsArray($eventCategory);
    $this->assertEquals('event', $eventCategory['name']);
    $this->assertNotEmpty($eventCategory['label']);

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

  public function testItDoesNotRegisterCouponPatternsWhenWooCommerceVersionIsBelowMinimum(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn('10.7.0');
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

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
    $this->assertContains('mailpoet/welcome-email-content', $patternNames);

    // Should include WooCommerce patterns that don't require coupon block
    $this->assertContains('mailpoet/first-purchase-thank-you', $patternNames);
    $this->assertContains('mailpoet/post-purchase-thank-you', $patternNames);
    $this->assertContains('mailpoet/product-purchase-follow-up', $patternNames);
    $this->assertContains('mailpoet/tag-purchase-follow-up', $patternNames);
    $this->assertContains('mailpoet/category-purchase-follow-up', $patternNames);
    $this->assertContains('mailpoet/ask-for-review-post-purchase', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-content', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-reminder-content', $patternNames);

    // Should NOT include generated coupon block patterns (require WooCommerce 10.8.0+)
    $this->assertNotContains('mailpoet/welcome-with-discount-email-content', $patternNames);
    $this->assertNotContains('mailpoet/win-back-customer', $patternNames);
    $this->assertNotContains('mailpoet/abandoned-cart-with-discount-content', $patternNames);

    // Verify total count (all patterns except 3 coupon patterns)
    $this->assertCount(16, $blockPatterns);
  }

  /**
   * @dataProvider dataProviderForWooCommerceVersionsWithCouponSupport
   */
  public function testItRegistersCouponPatternsForWooCommerceVersionsWithCouponSupport(string $version): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn($version);
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $patternNames = array_column($blockPatterns, 'name');

    // Generated coupon block patterns should be registered for WooCommerce 10.8.0+ (including RC/beta)
    $this->assertContains('mailpoet/welcome-with-discount-email-content', $patternNames);
    $this->assertContains('mailpoet/win-back-customer', $patternNames);
    $this->assertContains('mailpoet/abandoned-cart-with-discount-content', $patternNames);
  }

  public function dataProviderForWooCommerceVersionsWithCouponSupport(): array {
    return [
      'release version' => ['10.8.0'],
      'patch version' => ['10.8.1'],
      'minor version' => ['10.9.0'],
      'major version' => ['11.0.0'],
      'rc version' => ['10.8.0-rc.1'],
      'beta version' => ['10.8.0-beta.1'],
      'dev version' => ['10.8.0-dev'],
    ];
  }

  public function testItRegistersDiscountPatternsWithGeneratedCouponDefaults(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn('10.8.0');
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $patternsByName = array_column($blockPatterns, null, 'name');

    $this->assertStringContainsString('"source":"createNew"', $patternsByName['mailpoet/welcome-with-discount-email-content']['content']);
    $this->assertStringContainsString('"amount":10', $patternsByName['mailpoet/welcome-with-discount-email-content']['content']);
    $this->assertStringContainsString('"expiryDay":10', $patternsByName['mailpoet/welcome-with-discount-email-content']['content']);
    $this->assertStringContainsString('"amount":15', $patternsByName['mailpoet/win-back-customer']['content']);
    $this->assertStringContainsString('"expiryDay":1', $patternsByName['mailpoet/abandoned-cart-with-discount-content']['content']);
  }

  public function testAskForReviewPatternContainsReviewButtonWithOrderReviewUrlTag(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn('10.8.0');
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $content = $patterns->getPatternContent('ask-for-review-post-purchase');

    $this->assertIsString($content);
    $this->assertStringContainsString('wp:button', $content);
    $this->assertStringContainsString('"url":"[woocommerce/order-review-url]"', $content);
    $this->assertStringContainsString('href="[woocommerce/order-review-url]"', $content);
    $this->assertStringNotContainsString('data-link-href="[woocommerce/order-review-url]"', $content);
    $this->assertStringContainsString('[woocommerce/order-review-url]', $content);
    $this->assertStringContainsString('How was your experience?', $content);
  }

  public function testItDoesNotRegisterAskForReviewPatternWhenOrderReviewUrlIsUnsupported(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn('10.8.0');
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(false);

    $patterns = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );

    $patterns->registerPatterns();
    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $patternNames = array_column($blockPatterns, 'name');

    $this->assertNotContains('mailpoet/ask-for-review-post-purchase', $patternNames);
    $this->assertNull($patterns->getPatternContent('ask-for-review-post-purchase'));
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
    $this->assertNotContains('mailpoet/tag-purchase-follow-up', $patternNames);
    $this->assertNotContains('mailpoet/category-purchase-follow-up', $patternNames);
    $this->assertNotContains('mailpoet/ask-for-review-post-purchase', $patternNames);
    $this->assertNotContains('mailpoet/win-back-customer', $patternNames);
    $this->assertNotContains('mailpoet/abandoned-cart-content', $patternNames);
    $this->assertNotContains('mailpoet/abandoned-cart-reminder-content', $patternNames);
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
    $this->assertIsArray($registry->get_registered('sales-announcement'));
    $this->assertIsArray($registry->get_registered('educational-campaign'));
    $this->assertIsArray($registry->get_registered('event'));
    $this->assertIsArray($registry->get_registered('newsletter'));
    $this->assertIsArray($registry->get_registered('welcome'));

    // Should NOT include WooCommerce-dependent categories
    $purchaseCategory = $registry->get_registered('purchase');
    $this->assertNull($purchaseCategory);

    $abandonedCartCategory = $registry->get_registered('abandoned-cart');
    $this->assertNull($abandonedCartCategory);
  }

  public function testItAddsEmailContentToRestResponseForSplitPatterns(): void {
    $controller = $this->createControllerWithWooCommerce();
    $controller->registerPatterns();

    // Build a mock REST response containing pattern data (as the patterns REST endpoint would)
    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $responseData = [];
    foreach ($blockPatterns as $pattern) {
      $responseData[] = [
        'name' => $pattern['name'],
        'content' => $pattern['content'],
      ];
    }

    $response = new \WP_REST_Response($responseData);
    $request = new \WP_REST_Request('GET', '/wp/v2/block-patterns/patterns');

    $result = $controller->addEmailContentToRestResponse($response, [], $request);
    $this->assertInstanceOf(\WP_REST_Response::class, $result);
    /** @var array<int, array<string, string>> $data */
    $data = $result->get_data();
    $this->assertIsArray($data);

    // Patterns with split content should have email_content
    $splitPatternNames = [
      'mailpoet/first-purchase-thank-you',
      'mailpoet/post-purchase-thank-you',
      'mailpoet/product-purchase-follow-up',
      'mailpoet/tag-purchase-follow-up',
      'mailpoet/category-purchase-follow-up',
      'mailpoet/win-back-customer',
      'mailpoet/abandoned-cart-content',
      'mailpoet/abandoned-cart-reminder-content',
      'mailpoet/abandoned-cart-with-discount-content',
    ];

    foreach ($data as $pattern) {
      $this->assertIsArray($pattern);
      $name = (string)$pattern['name'];
      if (in_array($name, $splitPatternNames, true)) {
        $this->assertArrayHasKey('email_content', $pattern, "Pattern $name should have email_content");
        $this->assertNotEquals($pattern['content'], $pattern['email_content'], "Pattern $name email_content should differ from content");
        $this->assertStringContainsString('woocommerce/product-collection', (string)$pattern['email_content'], "Pattern $name email_content should contain product-collection block");
      } else {
        $this->assertArrayNotHasKey('email_content', $pattern, "Pattern $name should NOT have email_content");
      }
    }
  }

  public function testItDoesNotAddEmailContentForNonPatternRoutes(): void {
    $controller = $this->createControllerWithWooCommerce();
    $controller->registerPatterns();

    $response = new \WP_REST_Response(['some' => 'data']);
    $request = new \WP_REST_Request('GET', '/wp/v2/posts');

    $result = $controller->addEmailContentToRestResponse($response, [], $request);
    $this->assertInstanceOf(\WP_REST_Response::class, $result);
    $data = $result->get_data();

    $this->assertEquals(['some' => 'data'], $data);
  }

  public function testItDoesNotAddEmailContentWhenNoSplitPatterns(): void {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(false);

    $controller = new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );
    $controller->registerPatterns();

    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $responseData = [];
    foreach ($blockPatterns as $pattern) {
      $responseData[] = [
        'name' => $pattern['name'],
        'content' => $pattern['content'],
      ];
    }

    $response = new \WP_REST_Response($responseData);
    $request = new \WP_REST_Request('GET', '/wp/v2/block-patterns/patterns');

    $result = $controller->addEmailContentToRestResponse($response, [], $request);
    $this->assertInstanceOf(\WP_REST_Response::class, $result);
    /** @var array<int, array<string, string>> $data */
    $data = $result->get_data();
    $this->assertIsArray($data);

    // No WooCommerce = no split patterns = no email_content
    foreach ($data as $pattern) {
      $this->assertIsArray($pattern);
      $this->assertArrayNotHasKey('email_content', $pattern, 'Pattern ' . (string)$pattern['name'] . ' should NOT have email_content');
    }
  }

  private function createControllerWithWooCommerce(): PatternsController {
    $wooCommerceHelper = $this->createMock(WooCommerceHelper::class);
    $wooCommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $wooCommerceHelper->method('getWooCommerceVersion')->willReturn('10.8.0');
    $wooCommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(true);

    return new PatternsController(
      $this->diContainer->get(CdnAssetUrl::class),
      $this->diContainer->get(WPFunctions::class),
      $wooCommerceHelper
    );
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
