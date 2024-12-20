<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\NewsletterHtmlSanitizer;
use MailPoet\WP\Functions as WPFunctions;

class FooterAndHeaderTest extends \MailPoetTest {
  private Footer $footerRenderer;
  private Header $headerRenderer;

  private $block = [
    'text' => '<p>Hello</p>',
    'styles' => [
      'text' => [
        'fontSize' => 16,
      ],
      'block' => [
        'backgroundColor' => '#ffffff',
      ],
    ],
  ];

  public function _before() {
    parent::_before();
    $this->footerRenderer = new Footer(
      $this->diContainer->get(NewsletterHtmlSanitizer::class),
      $this->diContainer->get(WPFunctions::class)
    );
    $this->headerRenderer = new Header(
      $this->diContainer->get(NewsletterHtmlSanitizer::class),
      $this->diContainer->get(WPFunctions::class)
    );
  }

  public function testHeaderAndFooterSanitizeText(): void {
    $this->checkItSanitizesText($this->footerRenderer);
    $this->checkItSanitizesText($this->headerRenderer);
  }

  public function testHeaderAndFooterSanitizeStyles(): void {
    $this->checkItSanitizesStyles($this->footerRenderer);
    $this->checkItSanitizesStyles($this->headerRenderer);
  }

  public function checkItSanitizesText($renderer): void {
    $block = $this->block;
    // It removes tags that are not allowed
    $block['text'] = '<p><img src=x onerror="alert(1)"><script>alert(2);</script></p>';
    $result = $renderer->render($block);
    $this->assertStringNotContainsString('<img', $result);
    $this->assertStringNotContainsString('alert(1)', $result);
    $this->assertStringNotContainsString('<script>', $result);
    // Html entities should remain encoded
    $block['text'] = '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>';
    $result = $renderer->render($block);
    $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $result);
  }

  private function checkItSanitizesStyles($renderer): void {
    $block = $this->block;
    $block['styles']['block']['backgroundColor'] = '"> <script>alert(1);</script>';
    $result = $renderer->render($block);
    $this->assertStringNotContainsString('<script>', $result);
    $this->assertStringContainsString('&lt;script&gt;', $result);
  }
}
