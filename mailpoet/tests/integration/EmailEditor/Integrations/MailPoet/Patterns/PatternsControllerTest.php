<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

class PatternsControllerTest extends \MailPoetTest {
  private PatternsController $patterns;

  public function _before(): void {
    parent::_before();
    $this->patterns = $this->diContainer->get(PatternsController::class);
    $this->cleanupPatterns();
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

  private function cleanupPatterns() {
    $registry = \WP_Block_Patterns_Registry::get_instance();
    $blockPatterns = $registry->get_all_registered();
    foreach ($blockPatterns as $pattern) {
      $registry->unregister($pattern['name']);
    }
  }
}
