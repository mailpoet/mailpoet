<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;

class RendererTest extends \MailPoetTest {
  private Renderer $renderer;

  private \WP_Post $emailPost;

  public function _before(): void {
    parent::_before();
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->renderer = $this->diContainer->get(Renderer::class);
    $styles = [
      'spacing' => [
        'padding' => [
          'bottom' => '4px',
          'top' => '3px',
          'left' => '2px',
          'right' => '1px',
        ],
      ],
      'typography' => [
        'fontFamily' => 'Test Font Family',
      ],
      'color' => [
        'background' => '#123456',
      ],
    ];
    $themeJsonMock = $this->createMock(\WP_Theme_JSON::class);
    $themeJsonMock->method('get_data')->willReturn([
      'styles' => $styles,
    ]);
    $settingsControllerMock = $this->createMock(SettingsController::class);
    $settingsControllerMock->method('getEmailStyles')->willReturn($styles);
    $themeControllerMock = $this->createMock(ThemeController::class);
    $themeControllerMock->method('getTheme')->willReturn($themeJsonMock);
    $themeControllerMock->method('getStyles')->willReturn($styles);

    $this->renderer = $this->getServiceWithOverrides(Renderer::class, [
      'settingsController' => $settingsControllerMock,
      'themeController' => $themeControllerMock,
    ]);
    $this->emailPost = $this->tester->createPost([
      'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
    ]);
  }

  public function testItRendersTemplateWithContent(): void {
    $rendered = $this->renderer->render(
      $this->emailPost,
      'Subject',
      'Preheader content',
      'en',
      'noindex,nofollow'
    );
    verify($rendered['html'])->stringContainsString('Subject');
    verify($rendered['html'])->stringContainsString('Preheader content');
    verify($rendered['html'])->stringContainsString('noindex,nofollow');
    verify($rendered['html'])->stringContainsString('Hello!');

    verify($rendered['text'])->stringContainsString('Preheader content');
    verify($rendered['text'])->stringContainsString('Hello!');
  }

  public function testItInlinesStyles(): void {
    $stylesCallback = function ($styles) {
      return $styles . 'body { color: pink; }';
    };
    add_filter('mailpoet_email_renderer_styles', $stylesCallback);
    $rendered = $this->renderer->render($this->emailPost, 'Subject', '', 'en');
    $style = $this->getStylesValueForTag($rendered['html'], ['tag_name' => 'body']);
    verify($style)->stringContainsString('color: pink');
    remove_filter('mailpoet_email_renderer_styles', $stylesCallback);
  }

  public function testItInlinesBodyStyles(): void {
    $rendered = $this->renderer->render($this->emailPost, 'Subject', '', 'en');
    $style = $this->getStylesValueForTag($rendered['html'], ['tag_name' => 'body']);
    verify($style)->stringContainsString('margin: 0; padding: 0;');
  }

  public function testItInlinesWrappersStyles(): void {
    $rendered = $this->renderer->render($this->emailPost, 'Subject', '', 'en');

    // Verify body element styles
    $style = $this->getStylesValueForTag($rendered['html'], ['tag_name' => 'body']);
    verify($style)->stringContainsString('background-color: #123456');

    // Verify layout element styles
    $doc = new \DOMDocument();
    $doc->loadHTML($rendered['html']);
    $xpath = new \DOMXPath($doc);
    $wrapper = null;
    $nodes = $xpath->query('//div[contains(@class, "email_layout_wrapper")]');
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $wrapper = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $wrapper);
    $style = $wrapper->getAttribute('style');
    verify($style)->stringContainsString('background-color: #123456');
    verify($style)->stringContainsString('font-family: Test Font Family;');
    verify($style)->stringContainsString('padding-top: 3px;');
    verify($style)->stringContainsString('padding-bottom: 4px;');
  }

  private function getStylesValueForTag(string $html, array $query): ?string {
    $html = new \WP_HTML_Tag_Processor($html);
    if ($html->next_tag($query)) {
      return $html->get_attribute('style');
    }
    return null;
  }
}
