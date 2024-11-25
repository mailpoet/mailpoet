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

    $blockPatternContent = array_pop($blockPatterns);
    $this->assertIsArray($blockPatternContent);
    $this->assertArrayHasKey('name', $blockPatternContent);
    $this->assertArrayHasKey('content', $blockPatternContent);
    $this->assertArrayHasKey('title', $blockPatternContent);
    $this->assertArrayHasKey('categories', $blockPatternContent);
    $this->assertEquals('mailpoet/default-content', $blockPatternContent['name']);
    $this->assertStringContainsString('A one-column layout is great for simplified and concise content', $blockPatternContent['content']);
    $this->assertEquals('Default Email Content', $blockPatternContent['title']);
    $this->assertEquals(['email-contents'], $blockPatternContent['categories']);

    $blockPatternContentFull = array_pop($blockPatterns);
    $this->assertIsArray($blockPatternContentFull);
    $this->assertArrayHasKey('name', $blockPatternContentFull);
    $this->assertArrayHasKey('content', $blockPatternContentFull);
    $this->assertArrayHasKey('title', $blockPatternContentFull);
    $this->assertArrayHasKey('categories', $blockPatternContentFull);
    $this->assertEquals('mailpoet/default-content-full', $blockPatternContentFull['name']);
    $this->assertStringContainsString('A one-column layout is great for simplified and concise content', $blockPatternContentFull['content']);
    $this->assertEquals('Default Email Content with Header and Footer', $blockPatternContentFull['title']);
    $this->assertEquals(['email-contents'], $blockPatternContentFull['categories']);
  }

  private function cleanupPatterns() {
    $registry = \WP_Block_Patterns_Registry::get_instance();
    $blockPatterns = $registry->get_all_registered();
    foreach ($blockPatterns as $pattern) {
      $registry->unregister($pattern['name']);
    }
  }
}
