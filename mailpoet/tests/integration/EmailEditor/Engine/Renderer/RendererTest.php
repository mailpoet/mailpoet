<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

require_once __DIR__ . '/DummyBlockRenderer.php';

class RendererTest extends \MailPoetTest {
  /** @var Renderer */
  private $renderer;

  /** @var \WP_Post */
  private $emailPost;

  public function _before() {
    parent::_before();
    $this->renderer = $this->diContainer->get(Renderer::class);
    $this->emailPost = new \WP_Post((object)[
      'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
    ]);
  }

  public function testItRendersTemplateWithContent() {
    $rendered = $this->renderer->render(
      $this->emailPost,
      'Subject',
      'Preheader content',
      'en',
      'noindex,nofollow'
    );
    expect($rendered['html'])->stringContainsString('Subject');
    expect($rendered['html'])->stringContainsString('Preheader content');
    expect($rendered['html'])->stringContainsString('noindex,nofollow');
    expect($rendered['html'])->stringContainsString('Hello!');

    expect($rendered['text'])->stringContainsString('Preheader content');
    expect($rendered['text'])->stringContainsString('Hello!');
  }

  public function testItInlinesStyles() {
    $stylesCallback = function ($styles) {
      return $styles . 'body { color: pink; }';
    };
    add_filter('mailpoet_email_renderer_styles', $stylesCallback);
    $rendered = $this->renderer->render($this->emailPost, 'Subject', '', 'en');
    $doc = new \DOMDocument();
    $doc->loadHTML($rendered['html']);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//body');
    $body = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $body = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $body);
    $style = $body->getAttribute('style');
    expect($style)->stringContainsString('color:pink');
    remove_filter('mailpoet_email_renderer_styles', $stylesCallback);
  }

  public function testItInlinesEmailStyles() {
    $rendered = $this->renderer->render($this->emailPost, 'Subject', '', 'en');
    $doc = new \DOMDocument();
    $doc->loadHTML($rendered['html']);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//body');
    $body = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $body = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $body);
    $style = $body->getAttribute('style');
    expect($style)->stringContainsString('font-family:Arial,\'Helvetica Neue\',Helvetica,sans-serif;');
  }
}
