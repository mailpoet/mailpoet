<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\EmailEditor;

require_once __DIR__ . '/DummyBlockRenderer.php';

class ContentRendererTest extends \MailPoetTest {
  /** @var ContentRenderer */
  private $renderer;

  /** @var \WP_Post */
  private $emailPost;

  public function _before(): void {
    parent::_before();
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->renderer = $this->diContainer->get(ContentRenderer::class);
    $this->emailPost = new \WP_Post((object)[
      'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
    ]);
  }

  public function testItRendersContent(): void {
    $content = $this->renderer->render(
      $this->emailPost
    );
    verify($content)->stringContainsString('Hello!');
  }

  public function testItInlinesStylesAddedViaHook(): void {
    $stylesCallback = function ($styles) {
      return $styles . 'p { color: pink; }';
    };
    add_filter('mailpoet_email_content_renderer_styles', $stylesCallback);
    $rendered = $this->renderer->render($this->emailPost);
    $paragraphStyles = $this->getStylesValueForTag($rendered, 'p');
    verify($paragraphStyles)->stringContainsString('color:pink');
    remove_filter('mailpoet_email_content_renderer_styles', $stylesCallback);
  }

  public function testItInlinesContentStyles(): void {
    $rendered = $this->renderer->render($this->emailPost);
    $paragraphStyles = $this->getStylesValueForTag($rendered, 'p');
    verify($paragraphStyles)->stringContainsString('margin:0');
    verify($paragraphStyles)->stringContainsString('display:block');
  }

  private function getStylesValueForTag($html, $tag): ?string {
    $html = new \WP_HTML_Tag_Processor($html);
    if ($html->next_tag($tag)) {
      return $html->get_attribute('style');
    }
    return null;
  }
}
