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

    $threeColumnContent = array_pop($blockPatterns);
    $this->assertIsArray($threeColumnContent);
    $this->assertArrayHasKey('name', $threeColumnContent);
    $this->assertArrayHasKey('content', $threeColumnContent);
    $this->assertArrayHasKey('title', $threeColumnContent);
    $this->assertArrayHasKey('categories', $threeColumnContent);
    $this->assertEquals('mailpoet/3-column-content', $threeColumnContent['name']);
    $this->assertStringContainsString('A three-column layout organizes information into sections', $threeColumnContent['content']);
    $this->assertEquals('3 Columns', $threeColumnContent['title']);
    $this->assertEquals(['email-contents'], $threeColumnContent['categories']);

    $twoColumnContent = array_pop($blockPatterns);
    $this->assertIsArray($twoColumnContent);
    $this->assertArrayHasKey('name', $twoColumnContent);
    $this->assertArrayHasKey('content', $twoColumnContent);
    $this->assertArrayHasKey('title', $twoColumnContent);
    $this->assertArrayHasKey('categories', $twoColumnContent);
    $this->assertEquals('mailpoet/2-column-content', $twoColumnContent['name']);
    $this->assertStringContainsString('A two-column layout organizes information into sections', $twoColumnContent['content']);
    $this->assertEquals('2 Columns', $twoColumnContent['title']);
    $this->assertEquals(['email-contents'], $twoColumnContent['categories']);

    $oneColumnContent = array_pop($blockPatterns);
    $this->assertIsArray($oneColumnContent);
    $this->assertArrayHasKey('name', $oneColumnContent);
    $this->assertArrayHasKey('content', $oneColumnContent);
    $this->assertArrayHasKey('title', $oneColumnContent);
    $this->assertArrayHasKey('categories', $oneColumnContent);
    $this->assertEquals('mailpoet/1-column-content', $oneColumnContent['name']);
    $this->assertStringContainsString('A one-column layout is great for simplified and concise content', $oneColumnContent['content']);
    $this->assertEquals('1 Column', $oneColumnContent['title']);
    $this->assertEquals(['email-contents'], $oneColumnContent['categories']);
  }

  private function cleanupPatterns() {
    $registry = \WP_Block_Patterns_Registry::get_instance();
    $blockPatterns = $registry->get_all_registered();
    foreach ($blockPatterns as $pattern) {
      $registry->unregister($pattern['name']);
    }
  }
}
