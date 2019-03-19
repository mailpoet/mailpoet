<?php
namespace MailPoet\Newsletter\Renderer;

use MailPoet\Config\Env;
use MailPoet\Models\Newsletter;
use MailPoet\Services\Bridge;
use MailPoet\Util\License\License;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Renderer {
  public $blocks_renderer;
  public $columns_renderer;
  public $DOM_parser;
  public $CSS_inliner;
  public $newsletter;
  public $preview;
  public $premium_activated;
  public $mss_activated;
  private $template;
  const NEWSLETTER_TEMPLATE = 'Template.html';
  const FILTER_POST_PROCESS = 'mailpoet_rendering_post_process';

  /**
   * @param \MailPoet\Models\Newsletter|array $newsletter
   */
  function __construct($newsletter, $preview = false) {
    $this->newsletter = ($newsletter instanceof Newsletter) ? $newsletter->asArray() : $newsletter;
    $this->preview = $preview;
    $this->blocks_renderer = new Blocks\Renderer($this->newsletter);
    $this->columns_renderer = new Columns\Renderer();
    $this->DOM_parser = new pQuery();
    $this->CSS_inliner = new \MailPoet\Util\CSS();
    $this->template = file_get_contents(dirname(__FILE__) . '/' . self::NEWSLETTER_TEMPLATE);
    $this->premium_activated = License::getLicense();
    $bridge = new Bridge();
    $this->mss_activated = $bridge->isMPSendingServiceEnabled();
  }

  function render($type = false) {
    $newsletter = $this->newsletter;
    $body = (is_array($newsletter['body']))
      ? $newsletter['body']
      : array();
    $content = (array_key_exists('content', $body))
      ? $body['content']
      : array();
    $styles = (array_key_exists('globalStyles', $body))
      ? $body['globalStyles']
      : array();

    if (!$this->premium_activated && !$this->mss_activated && !$this->preview) {
      $content = $this->addMailpoetLogoContentBlock($content, $styles);
    }

    $content = $this->preProcessALC($content);
    $rendered_body = $this->renderBody($content);
    $rendered_styles = $this->renderStyles($styles);
    $custom_fonts_links = StylesHelper::getCustomFontsLinks($styles);

    $template = $this->injectContentIntoTemplate($this->template, array(
      htmlspecialchars($newsletter['subject']),
      $rendered_styles,
      $custom_fonts_links,
      $newsletter['preheader'],
      $rendered_body
    ));
    $template = $this->inlineCSSStyles($template);
    $template = $this->postProcessTemplate($template);

    $rendered_newsletter = array(
      'html' => $template,
      'text' => $this->renderTextVersion($template)
    );

    return ($type && !empty($rendered_newsletter[$type])) ?
      $rendered_newsletter[$type] :
      $rendered_newsletter;
  }

  /**
   * @param array $content
   * @return array
   */
  private function preProcessALC(array $content) {
    $blocks = array();
    $content_blocks = (array_key_exists('blocks', $content))
      ? $content['blocks']
      : array();
    foreach ($content_blocks as $block) {
      if ($block['type'] === 'automatedLatestContentLayout') {
        $blocks = array_merge(
          $blocks,
          $this->blocks_renderer->automatedLatestContentTransformedPosts($block)
        );
      } else {
        $blocks[] = $block;
      }
    }

    $content['blocks'] = $blocks;
    return $content;
  }

  /**
   * @param array $content
   * @return string
   */
  private function renderBody($content) {
    $blocks = (array_key_exists('blocks', $content))
      ? $content['blocks']
      : array();

    $_this = $this;
    $rendered_content = array_map(function($content_block) use($_this) {

      $columns_data = $_this->blocks_renderer->render($content_block);

      return $_this->columns_renderer->render(
        $content_block,
        $columns_data
      );
    }, $blocks);
    return implode('', $rendered_content);
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
   * @param string $content
   * @return string|string[]|null
   */
  private function injectContentIntoTemplate($template, $content) {
    return preg_replace_callback('/{{\w+}}/', function($matches) use (&$content) {
      return array_shift($content);
    }, $template);
  }

  /**
   * @param string $template
   * @return \pQuery\DomNode
   */
  private function inlineCSSStyles($template) {
    return $this->CSS_inliner->inlineCSS(null, $template);
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
   * @param string $template
   * @return string
   */
  private function postProcessTemplate($template) {
    $template_dom = $this->DOM_parser->parseStr($template);
    // replace spaces in image tag URLs
    foreach ($template_dom->query('img') as $image) {
      $image->src = str_replace(' ', '%20', $image->src);
    }
    $template = $template_dom->query('.mailpoet_template');
    // replace all !important tags except for in the body tag
    $template->html(
      str_replace('!important', '', $template->html())
    );
    // encode ampersand
    $template->html(
      str_replace('&', '&amp;', $template->html())
    );
    $template = WPFunctions::get()->applyFilters(
      self::FILTER_POST_PROCESS,
      $template_dom->__toString()
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
    $content['blocks'][] = array(
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => array(
        'block' => array(
          'backgroundColor' => (!empty($styles['body']['backgroundColor'])) ?
            $styles['body']['backgroundColor'] :
            'transparent'
        )
      ),
      'blocks' => array(
        array(
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' => array(
          ),
          'blocks' => array(
            array(
              'type' => 'image',
              'link' => 'http://www.mailpoet.com',
              'src' => Env::$assets_url . '/img/mailpoet_logo_newsletter.png',
              'fullWidth' => false,
              'alt' => 'MailPoet',
              'width' => '108px',
              'height' => '65px',
              'styles' => array(
                'block' => array(
                  'textAlign' => 'center'
                )
              )
            )
          )
        )
      )
    );
    return $content;
  }
}
