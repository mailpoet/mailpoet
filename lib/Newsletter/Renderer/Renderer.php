<?php

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Config\Env;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;
use MailPoet\Services\Bridge;
use MailPoet\Util\License\License;
use MailPoet\Util\pQuery\DomNode;
use MailPoet\WP\Functions as WPFunctions;

class Renderer {
  /** @var Blocks\Renderer */
  private $blocksRenderer;

  /** @var Columns\Renderer */
  private $columnsRenderer;

  /** @var Preprocessor */
  private $preprocessor;

  /** @var \MailPoetVendor\CSS */
  private $cSSInliner;

  /** @var Bridge */
  private $bridge;

  public $premiumActivated;
  public $mssActivated;
  private $template;
  const NEWSLETTER_TEMPLATE = 'Template.html';
  const FILTER_POST_PROCESS = 'mailpoet_rendering_post_process';

  public function __construct(
    Blocks\Renderer $blocksRenderer,
    Columns\Renderer $columnsRenderer,
    Preprocessor $preprocessor,
    \MailPoetVendor\CSS $cSSInliner,
    Bridge $bridge
  ) {
    $this->blocksRenderer = $blocksRenderer;
    $this->columnsRenderer = $columnsRenderer;
    $this->preprocessor = $preprocessor;
    $this->cSSInliner = $cSSInliner;
    $this->bridge = $bridge;
    $this->template = file_get_contents(dirname(__FILE__) . '/' . self::NEWSLETTER_TEMPLATE);
    $this->premiumActivated = License::getLicense();
    $this->mssActivated = $this->bridge->isMPSendingServiceEnabled();
  }

  /**
   * @param \MailPoet\Models\Newsletter|array $newsletter
   */
  public function render($newsletter, $preview = false, $type = false) {
    $newsletter = ($newsletter instanceof Newsletter) ? $newsletter->asArray() : $newsletter;
    $body = (is_array($newsletter['body']))
      ? $newsletter['body']
      : [];
    $content = (array_key_exists('content', $body))
      ? $body['content']
      : [];
    $styles = (array_key_exists('globalStyles', $body))
      ? $body['globalStyles']
      : [];

    if (!$this->premiumActivated && !$this->mssActivated && !$preview) {
      $content = $this->addMailpoetLogoContentBlock($content, $styles);
    }

    $content = $this->preprocessor->process($newsletter, $content);
    $renderedBody = $this->renderBody($newsletter, $content);
    $renderedStyles = $this->renderStyles($styles);
    $customFontsLinks = StylesHelper::getCustomFontsLinks($styles);

    $template = $this->injectContentIntoTemplate($this->template, [
      htmlspecialchars($newsletter['subject']),
      $renderedStyles,
      $customFontsLinks,
      EHelper::escapeHtmlText($newsletter['preheader']),
      $renderedBody,
    ]);
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
   * @param array $newsletter
   * @param array $content
   * @return string
   */
  private function renderBody($newsletter, $content) {
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
    $template = WPFunctions::get()->applyFilters(
      self::FILTER_POST_PROCESS,
      $templateDom->__toString()
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
              'link' => 'http://www.mailpoet.com',
              'src' => Env::$assetsUrl . '/img/mailpoet_logo_newsletter.png',
              'fullWidth' => false,
              'alt' => 'MailPoet',
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
