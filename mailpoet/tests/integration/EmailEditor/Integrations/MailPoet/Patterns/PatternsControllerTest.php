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

    $abandonedCartWithDiscount = array_pop($blockPatterns);
    $this->assertIsArray($abandonedCartWithDiscount);
    $this->assertArrayHasKey('name', $abandonedCartWithDiscount);
    $this->assertArrayHasKey('content', $abandonedCartWithDiscount);
    $this->assertArrayHasKey('title', $abandonedCartWithDiscount);
    $this->assertArrayHasKey('categories', $abandonedCartWithDiscount);
    $this->assertEquals('mailpoet/abandoned-cart-with-discount-content', $abandonedCartWithDiscount['name']);
    $this->assertStringContainsString('We Saved Your Cart + Little Surprise', $abandonedCartWithDiscount['content']);
    $this->assertEquals('Abandoned Cart with Discount', $abandonedCartWithDiscount['title']);
    $this->assertEquals(['abandoned-cart'], $abandonedCartWithDiscount['categories']);

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

    $winBackCustomer = array_pop($blockPatterns);
    $this->assertIsArray($winBackCustomer);
    $this->assertArrayHasKey('name', $winBackCustomer);
    $this->assertArrayHasKey('content', $winBackCustomer);
    $this->assertArrayHasKey('title', $winBackCustomer);
    $this->assertArrayHasKey('categories', $winBackCustomer);
    $this->assertEquals('mailpoet/win-back-customer', $winBackCustomer['name']);
    $this->assertStringContainsString('We Miss You', $winBackCustomer['content']);
    $this->assertEquals('Win Back Customer', $winBackCustomer['title']);
    $this->assertEquals(['purchase'], $winBackCustomer['categories']);

    $productPurchaseFollowUp = array_pop($blockPatterns);
    $this->assertIsArray($productPurchaseFollowUp);
    $this->assertArrayHasKey('name', $productPurchaseFollowUp);
    $this->assertArrayHasKey('content', $productPurchaseFollowUp);
    $this->assertArrayHasKey('title', $productPurchaseFollowUp);
    $this->assertArrayHasKey('categories', $productPurchaseFollowUp);
    $this->assertEquals('mailpoet/product-purchase-follow-up', $productPurchaseFollowUp['name']);
    $this->assertStringContainsString('Loving your [Product]', $productPurchaseFollowUp['content']);
    $this->assertEquals('Product Purchase Follow-Up', $productPurchaseFollowUp['title']);
    $this->assertEquals(['purchase'], $productPurchaseFollowUp['categories']);

    $postPurchaseThankYou = array_pop($blockPatterns);
    $this->assertIsArray($postPurchaseThankYou);
    $this->assertArrayHasKey('name', $postPurchaseThankYou);
    $this->assertArrayHasKey('content', $postPurchaseThankYou);
    $this->assertArrayHasKey('title', $postPurchaseThankYou);
    $this->assertArrayHasKey('categories', $postPurchaseThankYou);
    $this->assertEquals('mailpoet/post-purchase-thank-you', $postPurchaseThankYou['name']);
    $this->assertStringContainsString('thank you for your order', $postPurchaseThankYou['content']);
    $this->assertEquals('Post Purchase Thank You', $postPurchaseThankYou['title']);
    $this->assertEquals(['purchase'], $postPurchaseThankYou['categories']);

    $firstPurchaseThankYou = array_pop($blockPatterns);
    $this->assertIsArray($firstPurchaseThankYou);
    $this->assertArrayHasKey('name', $firstPurchaseThankYou);
    $this->assertArrayHasKey('content', $firstPurchaseThankYou);
    $this->assertArrayHasKey('title', $firstPurchaseThankYou);
    $this->assertArrayHasKey('categories', $firstPurchaseThankYou);
    $this->assertEquals('mailpoet/first-purchase-thank-you', $firstPurchaseThankYou['name']);
    $this->assertStringContainsString('Thank You for Your First Order', $firstPurchaseThankYou['content']);
    $this->assertEquals('First Purchase Thank You', $firstPurchaseThankYou['title']);
    $this->assertEquals(['purchase'], $firstPurchaseThankYou['categories']);

    $welcomeWithDiscountEmail = array_pop($blockPatterns);
    $this->assertIsArray($welcomeWithDiscountEmail);
    $this->assertArrayHasKey('name', $welcomeWithDiscountEmail);
    $this->assertArrayHasKey('content', $welcomeWithDiscountEmail);
    $this->assertArrayHasKey('title', $welcomeWithDiscountEmail);
    $this->assertArrayHasKey('categories', $welcomeWithDiscountEmail);
    $this->assertEquals('mailpoet/welcome-with-discount-email-content', $welcomeWithDiscountEmail['name']);
    $this->assertStringContainsString('Welcome to', $welcomeWithDiscountEmail['content']);
    $this->assertEquals('Welcome with Discount', $welcomeWithDiscountEmail['title']);
    $this->assertEquals(['welcome'], $welcomeWithDiscountEmail['categories']);

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

    $newArrivalsAnnouncement = array_pop($blockPatterns);
    $this->assertIsArray($newArrivalsAnnouncement);
    $this->assertArrayHasKey('name', $newArrivalsAnnouncement);
    $this->assertArrayHasKey('content', $newArrivalsAnnouncement);
    $this->assertArrayHasKey('title', $newArrivalsAnnouncement);
    $this->assertArrayHasKey('categories', $newArrivalsAnnouncement);
    $this->assertEquals('mailpoet/new-arrivals-announcement', $newArrivalsAnnouncement['name']);
    $this->assertStringContainsString('New arrivals are here', $newArrivalsAnnouncement['content']);
    $this->assertEquals('New Arrivals Announcement', $newArrivalsAnnouncement['title']);
    $this->assertEquals(['newsletter'], $newArrivalsAnnouncement['categories']);

    $productRestockNotification = array_pop($blockPatterns);
    $this->assertIsArray($productRestockNotification);
    $this->assertArrayHasKey('name', $productRestockNotification);
    $this->assertArrayHasKey('content', $productRestockNotification);
    $this->assertArrayHasKey('title', $productRestockNotification);
    $this->assertArrayHasKey('categories', $productRestockNotification);
    $this->assertEquals('mailpoet/product-restock-notification', $productRestockNotification['name']);
    $this->assertStringContainsString('back in stock', $productRestockNotification['content']);
    $this->assertEquals('Product Restock Notification', $productRestockNotification['title']);
    $this->assertEquals(['newsletter'], $productRestockNotification['categories']);

    $eventInvitation = array_pop($blockPatterns);
    $this->assertIsArray($eventInvitation);
    $this->assertArrayHasKey('name', $eventInvitation);
    $this->assertArrayHasKey('content', $eventInvitation);
    $this->assertArrayHasKey('title', $eventInvitation);
    $this->assertArrayHasKey('categories', $eventInvitation);
    $this->assertEquals('mailpoet/event-invitation', $eventInvitation['name']);
    $this->assertStringContainsString('Join us for', $eventInvitation['content']);
    $this->assertEquals('Event Invitation', $eventInvitation['title']);
    $this->assertEquals(['newsletter'], $eventInvitation['categories']);

    $educationalCampaign = array_pop($blockPatterns);
    $this->assertIsArray($educationalCampaign);
    $this->assertArrayHasKey('name', $educationalCampaign);
    $this->assertArrayHasKey('content', $educationalCampaign);
    $this->assertArrayHasKey('title', $educationalCampaign);
    $this->assertArrayHasKey('categories', $educationalCampaign);
    $this->assertEquals('mailpoet/educational-campaign', $educationalCampaign['name']);
    $this->assertStringContainsString('How it works', $educationalCampaign['content']);
    $this->assertEquals('Educational Campaign', $educationalCampaign['title']);
    $this->assertEquals(['newsletter'], $educationalCampaign['categories']);

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

    $purchaseCategory = $registry->get_registered('purchase');
    $this->assertIsArray($purchaseCategory);
    $this->assertEquals('purchase', $purchaseCategory['name']);
    $this->assertNotEmpty($purchaseCategory['label']);

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
