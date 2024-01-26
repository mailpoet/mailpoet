<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\Renderer\Renderer;
use MailPoet\EmailEditor\Integrations\Core\Initializer;

class RendererTest extends \MailPoetTest {
  /** @var Renderer */
  private $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = $this->diContainer->get(Renderer::class);
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->diContainer->get(Initializer::class)->initialize();
  }

  public function testItInlinesButtonDefaultStyles() {
    $emailPost = new \WP_Post((object)[
      'post_content' => '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button">Button</a></div><!-- /wp:button -->',
    ]);
    $rendered = $this->renderer->render($emailPost, 'Subject', '', 'en');
    $buttonHtml = $this->extractBlockHtml($rendered['html'], 'wp-block-button', 'td');
    verify($buttonHtml)->stringContainsString('color:#ffffff');
    verify($buttonHtml)->stringContainsString('padding:.7em 1.4em');
    verify($buttonHtml)->stringContainsString('background:#32373c');
  }

  public function testItInlinesHeadingFontSize() {
    $emailPost = new \WP_Post((object)[
      'post_content' => '<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"large"}}} --><h1 class="wp-block-heading">Hello</h1><!-- /wp:heading -->',
    ]);
    $rendered = $this->renderer->render($emailPost, 'Subject', '', 'en');
    $headingHtml = $this->extractBlockHtml($rendered['html'], 'wp-block-heading', 'h1');
    verify($headingHtml)->stringContainsString('font-size:48px'); // large is 48px
  }

  public function testItInlinesHeadingColors() {
    $emailPost = new \WP_Post((object)[
      'post_content' => '<!-- wp:heading {"level":1, "backgroundColor":"black", "textColor":"luminous-vivid-orange"} --><h1 class="wp-block-heading has-luminous-vivid-orange-color has-black-background-color">Hello</h1><!-- /wp:heading -->',
    ]);
    $rendered = $this->renderer->render($emailPost, 'Subject', '', 'en');
    $headingWrapperStyle = $this->extractBlockStyle($rendered['html'], 'has-luminous-vivid-orange-color', 'td');
    verify($headingWrapperStyle)->stringContainsString('color:#ff6900'); // luminous-vivid-orange is #ff6900
    verify($headingWrapperStyle)->stringContainsString('background-color:#000000'); // black is #000000
  }

  public function testItInlinesParagraphColors() {
    $emailPost = new \WP_Post((object)[
      'post_content' => '<!-- wp:paragraph {style":{"color":{"background":"black", "text":"luminous-vivid-orange"}}} --><p class="has-luminous-vivid-orange-color has-black-background-color">Hello</p><!-- /wp:paragraph -->',
    ]);
    $rendered = $this->renderer->render($emailPost, 'Subject', '', 'en');
    $paragraphWrapperStyle = $this->extractBlockStyle($rendered['html'], 'has-luminous-vivid-orange-color', 'td');
    verify($paragraphWrapperStyle)->stringContainsString('color:#ff6900'); // luminous-vivid-orange is #ff6900
    verify($paragraphWrapperStyle)->stringContainsString('background-color:#000000'); // black is #000000
  }

  private function extractBlockHtml(string $html, string $blockClass, string $tag): string {
    $doc = new \DOMDocument();
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//' . $tag . '[contains(@class, "' . $blockClass . '")]');
    $block = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $block = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $block);
    $this->assertInstanceOf(\DOMDocument::class, $block->ownerDocument);
    return (string)$block->ownerDocument->saveHTML($block);
  }

  private function extractBlockStyle(string $html, string $blockClass, string $tag): string {
    $doc = new \DOMDocument();
    $doc->loadHTML($html);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//' . $tag . '[contains(@class, "' . $blockClass . '")]');
    $block = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $block = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $block);
    return $block->getAttribute('style');
  }
}
