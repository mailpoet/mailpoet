<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\Util\pQuery\DomNode;
use MailPoetVendor\Html2Text\Html2Text;

class Renderer {

  /** @var \MailPoetVendor\CSS */
  private $cssInliner;

  /** @var BodyRenderer */
  private $bodyRenderer;

  const TEMPLATE_FILE = 'template.html';
  const STYLES_FILE = 'styles.css';

  /**
   * @param \MailPoetVendor\CSS $cssInliner
   */
  public function __construct(
    \MailPoetVendor\CSS $cssInliner,
    BodyRenderer $bodyRenderer
  ) {
    $this->cssInliner = $cssInliner;
    $this->bodyRenderer = $bodyRenderer;
  }

  public function render(string $postContent, string $subject, string $preHeader, string $language, $metaRobots = ''): array {
    $renderedBody = $this->bodyRenderer->renderBody($postContent);
    $styles = (string)file_get_contents(dirname(__FILE__) . '/' . self::STYLES_FILE);

    /**
     * {{email_language}}
     * {{email_subject}}
     * {{email_meta_robots}}
     * {{email_styles}}
     * {{email_preheader}}
     * {{email_body}}
     */
    $templateWithContents = $this->injectContentIntoTemplate(
      (string)file_get_contents(dirname(__FILE__) . '/' . self::TEMPLATE_FILE),
      [
        $language,
        esc_html($subject),
        $metaRobots,
        $styles,
        esc_html($preHeader),
        $renderedBody,
      ]
    );

    $templateWithContentsDom = $this->inlineCSSStyles($templateWithContents);
    $templateWithContents = $this->postProcessTemplate($templateWithContentsDom);
    return [
      'html' => $templateWithContents,
      'text' => $this->renderTextVersion($templateWithContents),
    ];
  }

  private function injectContentIntoTemplate($template, array $content) {
    return preg_replace_callback('/{{\w+}}/', function($matches) use (&$content) {
      return array_shift($content);
    }, $template);
  }

  /**
   * @param string $template
   * @return DomNode
   */
  private function inlineCSSStyles($template) {
    return $this->cssInliner->inlineCSS($template);
  }

  /**
   * @param string $template
   * @return string
   */
  private function renderTextVersion($template) {
    $template = (mb_detect_encoding($template, 'UTF-8', true)) ? $template : mb_convert_encoding($template, 'UTF-8', mb_list_encodings());
    return @Html2Text::convert($template);
  }

  /**
   * @param DomNode $templateDom
   * @return string
   */
  private function postProcessTemplate(DomNode $templateDom) {
    // replace spaces in image tag URLs
    foreach ($templateDom->query('img') as $image) {
      $image->src = str_replace(' ', '%20', $image->src);
    }
    // because tburry/pquery contains a bug and replaces the opening non mso condition incorrectly we have to replace the opening tag with correct value
    $template = $templateDom->__toString();
    $template = str_replace('<!--[if !mso]><![endif]-->', '<!--[if !mso]><!-- -->', $template);
    return $template;
  }
}
