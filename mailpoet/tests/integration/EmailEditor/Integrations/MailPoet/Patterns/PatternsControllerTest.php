<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

class PatternsControllerTest extends \MailPoetTest {
  private PatternsController $patterns;

  public function _before(): void {
    parent::_before();
    $this->patterns = $this->diContainer->get(PatternsController::class);
    $this->cleanupPatterns();
    $this->cleanupPatternCategories();
  }

  public function testItRegistersPatterns(): void {
    $this->patterns->registerPatterns();
    $blockPatterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();

    $abandonedCart = array_pop($blockPatterns);
    $this->assertIsArray($abandonedCart);
    $this->assertArrayHasKey('name', $abandonedCart);
    $this->assertArrayHasKey('content', $abandonedCart);
    $this->assertArrayHasKey('title', $abandonedCart);
    $this->assertArrayHasKey('categories', $abandonedCart);
    $this->assertEquals('mailpoet/abandoned-cart-content', $abandonedCart['name']);
    $this->assertStringContainsString('Don‘t let this gem slip away', $abandonedCart['content']);
    $this->assertEquals('Abandoned Cart', $abandonedCart['title']);
    $this->assertEquals(['abandoned-cart'], $abandonedCart['categories']);

    $welcomeEmail = array_pop($blockPatterns);
    $this->assertIsArray($welcomeEmail);
    $this->assertArrayHasKey('name', $welcomeEmail);
    $this->assertArrayHasKey('content', $welcomeEmail);
    $this->assertArrayHasKey('title', $welcomeEmail);
    $this->assertArrayHasKey('categories', $welcomeEmail);
    $this->assertEquals('mailpoet/welcome-email-content', $welcomeEmail['name']);
    $this->assertStringContainsString('Welcome to', $welcomeEmail['content']);
    $this->assertEquals('Welcome Email', $welcomeEmail['title']);
    $this->assertEquals(['welcome'], $welcomeEmail['categories']);

    $newProductsAnnouncement = array_pop($blockPatterns);
    $this->assertIsArray($newProductsAnnouncement);
    $this->assertArrayHasKey('name', $newProductsAnnouncement);
    $this->assertArrayHasKey('content', $newProductsAnnouncement);
    $this->assertArrayHasKey('title', $newProductsAnnouncement);
    $this->assertArrayHasKey('categories', $newProductsAnnouncement);
    $this->assertEquals('mailpoet/new-products-announcement', $newProductsAnnouncement['name']);
    $this->assertStringContainsString('Meet our newest product', $newProductsAnnouncement['content']);
    $this->assertEquals('New Products Announcement', $newProductsAnnouncement['title']);
    $this->assertEquals(['newsletter'], $newProductsAnnouncement['categories']);

    $saleAnnouncement = array_pop($blockPatterns);
    $this->assertIsArray($saleAnnouncement);
    $this->assertArrayHasKey('name', $saleAnnouncement);
    $this->assertArrayHasKey('content', $saleAnnouncement);
    $this->assertArrayHasKey('title', $saleAnnouncement);
    $this->assertArrayHasKey('categories', $saleAnnouncement);
    $this->assertEquals('mailpoet/sale-announcement', $saleAnnouncement['name']);
    $this->assertStringContainsString('sitewide sale is officially ON', $saleAnnouncement['content']);
    $this->assertEquals('Sale Announcement', $saleAnnouncement['title']);
    $this->assertEquals(['newsletter'], $saleAnnouncement['categories']);

    $newsletter = array_pop($blockPatterns);
    $this->assertIsArray($newsletter);
    $this->assertArrayHasKey('name', $newsletter);
    $this->assertArrayHasKey('content', $newsletter);
    $this->assertArrayHasKey('title', $newsletter);
    $this->assertArrayHasKey('categories', $newsletter);
    $this->assertEquals('mailpoet/newsletter-content', $newsletter['name']);
    $this->assertStringContainsString('Weekly Newsletter', $newsletter['content']);
    $this->assertEquals('Newsletter', $newsletter['title']);
    $this->assertEquals(['newsletter'], $newsletter['categories']);
  }

  public function testItRegistersPatternCategories(): void {
    $this->patterns->registerPatterns();
    $registry = \WP_Block_Pattern_Categories_Registry::get_instance();

    $newsletterCategory = $registry->get_registered('newsletter');
    $this->assertIsArray($newsletterCategory);
    $this->assertEquals('newsletter', $newsletterCategory['name']);
    $this->assertNotEmpty($newsletterCategory['label']);

    $welcomeCategory = $registry->get_registered('welcome');
    $this->assertIsArray($welcomeCategory);
    $this->assertEquals('welcome', $welcomeCategory['name']);
    $this->assertNotEmpty($welcomeCategory['label']);

    $abandonedCartCategory = $registry->get_registered('abandoned-cart');
    $this->assertIsArray($abandonedCartCategory);
    $this->assertEquals('abandoned-cart', $abandonedCartCategory['name']);
    $this->assertNotEmpty($abandonedCartCategory['label']);
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
