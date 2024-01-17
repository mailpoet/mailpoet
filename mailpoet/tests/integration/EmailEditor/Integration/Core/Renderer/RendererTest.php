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
    $doc = new \DOMDocument();
    $doc->loadHTML($rendered['html']);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//td[contains(@class, "wp-block-button")]');
    $button = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $button = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $button);
    $this->assertInstanceOf(\DOMDocument::class, $button->ownerDocument);
    $buttonHtml = $button->ownerDocument->saveHTML($button);
    verify($buttonHtml)->stringContainsString('color:#ffffff');
    verify($buttonHtml)->stringContainsString('padding:.7em 1.4em');
    verify($buttonHtml)->stringContainsString('background:#32373c');
  }
}
