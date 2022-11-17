<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Config\Env;
use MailPoet\Config\ServicesChecker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\pQuery\DomNode;
use MailPoet\WP\Functions as WPFunctions;

class Renderer {
  const NEWSLETTER_TEMPLATE = 'Template.html';
  const FILTER_POST_PROCESS = 'mailpoet_rendering_post_process';

  /** @var Blocks\Renderer */
  private $blocksRenderer;

  /** @var Columns\Renderer */
  private $columnsRenderer;

  /** @var Preprocessor */
  private $preprocessor;

  /** @var \MailPoetVendor\CSS */
  private $cSSInliner;

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(
    Blocks\Renderer $blocksRenderer,
    Columns\Renderer $columnsRenderer,
    Preprocessor $preprocessor,
    \MailPoetVendor\CSS $cSSInliner,
    ServicesChecker $servicesChecker
  ) {
    $this->blocksRenderer = $blocksRenderer;
    $this->columnsRenderer = $columnsRenderer;
    $this->preprocessor = $preprocessor;
    $this->cSSInliner = $cSSInliner;
    $this->servicesChecker = $servicesChecker;
  }

  public function render(NewsletterEntity $newsletter, SendingTask $sendingTask = null, $type = false) {
    return $this->_render($newsletter, $sendingTask, $type);
  }

  public function renderAsPreview(NewsletterEntity $newsletter, $type = false, ?string $subject = null) {
    return $this->_render($newsletter, null, $type, true, $subject);
  }

  private function _render(NewsletterEntity $newsletter, SendingTask $sendingTask = null, $type = false, $preview = false, $subject = null) {
    $body = (is_array($newsletter->getBody()))
      ? $newsletter->getBody()
      : [];
    $content = (array_key_exists('content', $body))
      ? $body['content']
      : [];
    $styles = (array_key_exists('globalStyles', $body))
      ? $body['globalStyles']
      : [];

    if (
      !$this->servicesChecker->isUserActivelyPaying() && !$preview
    ) {
      $content = $this->addMailpoetLogoContentBlock($content, $styles);
    }

    $metaRobots = $preview ? '<meta name="robots" content="noindex, nofollow" />' : '';
    $content = $this->preprocessor->process($newsletter, $content, $preview, $sendingTask);
    $renderedBody = $this->renderBody($newsletter, $content);
    $renderedStyles = $this->renderStyles($styles);
    $customFontsLinks = StylesHelper::getCustomFontsLinks($styles);

    $template = $this->injectContentIntoTemplate(
      (string)file_get_contents(dirname(__FILE__) . '/' . self::NEWSLETTER_TEMPLATE),
      [
        $metaRobots,
        htmlspecialchars($subject ?: $newsletter->getSubject()),
        $renderedStyles,
        $customFontsLinks,
        EHelper::escapeHtmlText($newsletter->getPreheader()),
        $renderedBody,
      ]
    );
    if ($template === null) {
      $template = '';
    }
    $templateDom = $this->inlineCSSStyles($template);
    $template = $this->postProcessTemplate($templateDom);

    $renderedNewsletter = [
      'html' => $template,
      'text' => $this->renderTextVersion($template),
    ];

    return ($type && !empty($renderedNewsletter[$type])) ?
      $renderedNewsletter[$type] :
      $renderedNewsletter;
  }

  /**
   * @param NewsletterEntity $newsletter
   * @param array $content
   * @return string
   */
  private function renderBody(NewsletterEntity $newsletter, array $content) {
    $blocks = (array_key_exists('blocks', $content))
      ? $content['blocks']
      : [];

    $renderedContent = [];
    foreach ($blocks as $contentBlock) {
      $columnsData = $this->blocksRenderer->render($newsletter, $contentBlock);

      $renderedContent[] = $this->columnsRenderer->render(
        $contentBlock,
        $columnsData
      );
    }
    return implode('', $renderedContent);
  }

  /**
   * @param array $styles
   * @return string
   */
  private function renderStyles(array $styles) {
    $css = '';
    foreach ($styles as $selector => $style) {
      switch ($selector) {
        case 'text':
          $selector = 'td.mailpoet_paragraph, td.mailpoet_blockquote, li.mailpoet_paragraph';
          break;
        case 'body':
          $selector = 'body, .mailpoet-wrapper';
          break;
        case 'link':
          $selector = '.mailpoet-wrapper a';
          break;
        case 'wrapper':
          $selector = '.mailpoet_content-wrapper';
          break;
      }

      if (!is_array($style)) {
        continue;
      }

      $css .= StylesHelper::setStyle($style, $selector);
    }
    return $css;
  }

  /**
   * @param string $template
   * @param string[] $content
   * @return string|null
   */
  private function injectContentIntoTemplate($template, $content) {
    return preg_replace_callback('/{{\w+}}/', function($matches) use (&$content) {
      return array_shift($content);
    }, $template);
  }

  /**
   * @param string $template
   * @return DomNode
   */
  private function inlineCSSStyles($template) {
    return $this->cSSInliner->inlineCSS($template);
  }

  /**
   * @param string $template
   * @return string
   */
  private function renderTextVersion($template) {
    $template = (mb_detect_encoding($template, 'UTF-8', true)) ? $template : utf8_encode($template);
    return @\Html2Text\Html2Text::convert($template);
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
    $template = WPFunctions::get()->applyFilters(
      self::FILTER_POST_PROCESS,
      $template
    );
    return $template;
  }

  /**
   * @param array $content
   * @param array $styles
   * @return array
   */
  private function addMailpoetLogoContentBlock(array $content, array $styles) {
    if (empty($content['blocks'])) return $content;
    $content['blocks'][] = [
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => [
        'block' => [
          'backgroundColor' => (!empty($styles['body']['backgroundColor'])) ?
            $styles['body']['backgroundColor'] :
            'transparent',
        ],
      ],
      'blocks' => [
        [
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' => [
          ],
          'blocks' => [
            [
              'type' => 'image',
              'link' => 'https://www.mailpoet.com/?ref=free-plan-user-email&utm_source=free_plan_user_email&utm_medium=email',
              'src' => Env::$assetsUrl . '/img/mailpoet_logo_newsletter.png',
              'fullWidth' => false,
              'alt' => 'Email Marketing Powered by MailPoet',
              'width' => '108px',
              'height' => '65px',
              'styles' => [
                'block' => [
                  'textAlign' => 'center',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    return $content;
  }
}
