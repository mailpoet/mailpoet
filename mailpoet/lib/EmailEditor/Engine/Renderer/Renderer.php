<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\StylesController;
use MailPoet\Util\pQuery\DomNode;
use MailPoetVendor\Html2Text\Html2Text;

class Renderer {

  /** @var \MailPoetVendor\CSS */
  private $cssInliner;

  /** @var BlocksRenderer */
  private $blocksRenderer;

  /** @var Preprocessor */
  private $preprocessor;

  /** @var StylesController */
  private $stylesController;

  const TEMPLATE_FILE = 'template.html';
  const TEMPLATE_STYLES_FILE = 'styles.css';

  /**
   * @param \MailPoetVendor\CSS $cssInliner
   */
  public function __construct(
    \MailPoetVendor\CSS $cssInliner,
    Preprocessor $preprocessor,
    BlocksRenderer $blocksRenderer,
    StylesController $stylesController
  ) {
    $this->cssInliner = $cssInliner;
    $this->preprocessor = $preprocessor;
    $this->blocksRenderer = $blocksRenderer;
    $this->stylesController = $stylesController;
  }

  public function render(\WP_Post $post, string $subject, string $preHeader, string $language, $metaRobots = ''): array {
    $parser = new \WP_Block_Parser();
    $parsedBlocks = $parser->parse($post->post_content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    $parsedBlocks = $this->preprocessor->preprocess($parsedBlocks, $this->stylesController->getEmailLayoutStyles());
    $renderedBody = $this->blocksRenderer->render($parsedBlocks);

    $styles = (string)file_get_contents(dirname(__FILE__) . '/' . self::TEMPLATE_STYLES_FILE);
    $styles .= $this->stylesController->getEmailContentStyles();
    $styles = apply_filters('mailpoet_email_renderer_styles', $styles, $post);

    $template = (string)file_get_contents(dirname(__FILE__) . '/' . self::TEMPLATE_FILE);

    // Apply layout styles
    $layoutStyles = $this->stylesController->getEmailLayoutStyles();
    $template = str_replace(
      ['{{width}}', '{{background}}', '{{padding_top}}', '{{padding_right}}', '{{padding_bottom}}', '{{padding_left}}'],
      [$layoutStyles['width'], $layoutStyles['background'], $layoutStyles['padding']['top'], $layoutStyles['padding']['right'], $layoutStyles['padding']['bottom'], $layoutStyles['padding']['left']],
      $template
    );

    /**
     * Replace template variables
     * {{email_language}}
     * {{email_subject}}
     * {{email_meta_robots}}
     * {{email_template_styles}}
     * {{email_preheader}}
     * {{email_body}}
     */
    $templateWithContents = $this->injectContentIntoTemplate(
      $template,
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
