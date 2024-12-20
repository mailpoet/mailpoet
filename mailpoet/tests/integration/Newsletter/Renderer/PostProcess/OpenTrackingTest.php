<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\PostProcess\OpenTracking;

class OpenTrackingTest extends \MailPoetTest {
  public function testItAddsTrackingImage(): void {
    $template = '<html><body><p>text</p></body></html>';
    $result = OpenTracking::process($template);
    $this->assertStringContainsString('<img', $result);
    $this->assertStringContainsString('src="' . Links::DATA_TAG_OPEN . '"', $result);
  }

  public function testItReturnsOriginalInputIfBodyTagIsMissing(): void {
    $template = '<html><p>text</p></html>';
    $result = OpenTracking::process($template);
    $this->assertEquals($template, $result);
  }

  public function testItPreservesHTMLEntities(): void {
    $template = '<html><body><p>text</p>&lt;img src="x" onerror="alert(1)"&gt;</body></html>';
    $result = OpenTracking::process($template);
    $this->assertStringContainsString('&lt;img src="x" onerror="alert(1)"&gt;', $result);
  }
}
