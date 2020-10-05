<?php

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;
use MailPoet\RuntimeException;
use MailPoet\Services\Bridge;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\License\License;
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

  /** @var Bridge */
  private $bridge;

  /** @var License */
  private $license;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    Blocks\Renderer $blocksRenderer,
    Columns\Renderer $columnsRenderer,
    Preprocessor $preprocessor,
    \MailPoetVendor\CSS $cSSInliner,
    Bridge $bridge,
    NewslettersRepository $newslettersRepository,
    License $license
  ) {
    $this->blocksRenderer = $blocksRenderer;
    $this->columnsRenderer = $columnsRenderer;
    $this->preprocessor = $preprocessor;
    $this->cSSInliner = $cSSInliner;
    $this->bridge = $bridge;
    $this->license = $license;
    $this->newslettersRepository = $newslettersRepository;
  }

  /**
   * This is only temporary, when all calls are refactored to doctrine and only entity is passed we don't need this
   * @param \MailPoet\Models\Newsletter|NewsletterEntity $newsletter
   * @return NewsletterEntity|null
   */
  private function getNewsletter($newsletter) {
    if ($newsletter instanceof NewsletterEntity) return $newsletter;
    if ($newsletter instanceof Newsletter) {
      $newsletterId = $newsletter->id;
    }
    return $this->newslettersRepository->findOneById($newsletterId);
  }

  public function render($newsletter, SendingTask $sendingTask = null, $type = false) {
    return $this->_render($newsletter, $sendingTask, $type);
  }

  public function renderAsPreview($newsletter, $type = false) {
    return $this->_render($newsletter, null, $type, true);
  }

  private function _render($newsletter, SendingTask $sendingTask = null, $type = false, $preview = false) {
    $newsletter = $this->getNewsletter($newsletter);
    if (!$newsletter instanceof NewsletterEntity) {
      throw new RuntimeException('Newsletter was not found');
    }
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
      !$this->license->hasLicense()
      && !$this->bridge->isMailpoetSendingServiceEnabled()
      && !$preview
    ) {
      $content = $this->addMailpoetLogoContentBlock($content, $styles);
    }

    $content = $this->preprocessor->process($newsletter, $content, $preview, $sendingTask);
    $renderedBody = $this->renderBody($newsletter, $content);
    $renderedStyles = $this->renderStyles($styles);
    $customFontsLinks = StylesHelper::getCustomFontsLinks($styles);

    $template = $this->injectContentIntoTemplate(
      (string)file_get_contents(dirname(__FILE__) . '/' . self::NEWSLETTER_TEMPLATE),
      [
        htmlspecialchars($newsletter->getSubject()),
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
