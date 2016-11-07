<?php
namespace MailPoet\Newsletter\Renderer;

if(!defined('ABSPATH')) exit;

class Renderer {
  public $blocks_renderer;
  public $columns_renderer;
  public $DOM_parser;
  public $CSS_inliner;
  public $newsletter;
  public $preview;
  const NEWSLETTER_TEMPLATE = 'Template.html';
  const POST_PROCESS_FILTER = 'mailpoet_rendering_post_process';

  function __construct($newsletter, $preview = false) {
    // TODO: remove ternary condition, refactor to use model objects
    $this->newsletter = (is_object($newsletter)) ? $newsletter->asArray() : $newsletter;
    $this->preview = $preview;
    $this->blocks_renderer = new Blocks\Renderer($this->newsletter, $this->preview);
    $this->columns_renderer = new Columns\Renderer();
    $this->DOM_parser = new \pQuery();
    $this->CSS_inliner = new \MailPoet\Util\CSS();
    $this->template = file_get_contents(dirname(__FILE__) . '/' . self::NEWSLETTER_TEMPLATE);
  }

  function render() {
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

    $rendered_body = $this->renderBody($content);
    $rendered_styles = $this->renderStyles($styles);

    $template = $this->injectContentIntoTemplate($this->template, array(
      $newsletter['subject'],
      $rendered_styles,
      $newsletter['preheader'],
      $rendered_body
    ));
    $template = $this->inlineCSSStyles($template);
    $template = $this->postProcessTemplate($template);

    return array(
      'html' => $template,
      'text' => $this->renderTextVersion($template)
    );
  }

  function renderBody($content) {
    $blocks = (array_key_exists('blocks', $content))
      ? $content['blocks']
      : array();

    $_this = $this;
    $rendered_content = array_map(function($content_block) use($_this) {
      $column_count = count($content_block['blocks']);
      $column_data = $_this->blocks_renderer->render(
        $content_block,
        $column_count
      );
      return $_this->columns_renderer->render(
        $content_block['styles'],
        $column_count,
        $column_data
      );
    }, $blocks);
    return implode('', $rendered_content);
  }

  function renderStyles($styles) {
    $css = '';
    foreach($styles as $selector => $style) {
      switch($selector) {
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

  function injectContentIntoTemplate($template, $content) {
    return preg_replace_callback('/{{\w+}}/', function($matches) use (&$content) {
      return array_shift($content);
    }, $template);
  }

  function inlineCSSStyles($template) {
    return $this->CSS_inliner->inlineCSS(null, $template);
  }

  function renderTextVersion($template) {
    $template = utf8_encode($template);
    return \Html2Text\Html2Text::convert($template);
  }

  function postProcessTemplate($template) {
    $DOM = $this->DOM_parser->parseStr($template);
    $template = $DOM->query('.mailpoet_template');
    // replace all !important tags except for in the body tag
    $template->html(
      str_replace('!important', '', $template->html())
    );
    // encode ampersand
    $template->html(
      str_replace('&', '&amp;', $template->html())
    );
    $template = apply_filters(
      self::POST_PROCESS_FILTER,
      $DOM->__toString()
    );
    return $template;
  }
}
