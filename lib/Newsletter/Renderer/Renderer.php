<?php
namespace MailPoet\Newsletter\Renderer;

if(!defined('ABSPATH')) exit;

class Renderer {
  public $template = 'Template.html';
  public $blocks_renderer;
  public $columns_renderer;
  public $DOM_parser;
  public $CSS_inliner;
  public $newsletter;

  function __construct($newsletter) {
    $this->blocks_renderer = new Blocks\Renderer();
    $this->columns_renderer = new Columns\Renderer();
    $this->DOM_parser = new \pQuery();
    $this->CSS_inliner = new \MailPoet\Util\CSS();
    $this->newsletter = $newsletter;
    $this->template = file_get_contents(dirname(__FILE__) . '/' . $this->template);
  }

  function render() {
    $newsletter_data = (is_array($this->newsletter['body'])) ?
      $this->newsletter['body'] :
      json_decode($this->newsletter['body'], true);
    $newsletter_body = $this->renderBody($newsletter_data['content']);
    $newsletter_styles = $this->renderStyles($newsletter_data['globalStyles']);
    $newsletter_subject = $this->newsletter['subject'];
    $newsletter_preheader = $this->newsletter['preheader'];
    $template = $this->injectContentIntoTemplate($this->template, array(
      $newsletter_subject,
      $newsletter_styles,
      $newsletter_preheader,
      $newsletter_body
    ));
    $template = $this->inlineCSSStyles($template);
    return $this->postProcessTemplate($template);
  }

  function renderBody($content) {
    $content = array_map(function ($content_block) {
      $column_count = count($content_block['blocks']);
      $column_data = $this->blocks_renderer->render($content_block, $column_count);
      return $this->columns_renderer->render(
        $content_block['styles'],
        $column_count,
        $column_data
      );
    }, $content['blocks']);
    return implode('', $content);
  }

  function renderStyles($styles) {
    $css = '';
    foreach($styles as $selector => $style) {
      switch($selector) {
      case 'h1':
        $selector = 'h1';
      break;
      case 'h2':
        $selector = 'h2';
      break;
      case 'h3':
        $selector = 'h3';
      break;
      case 'text':
        $selector = '.mailpoet_paragraph, .mailpoet_blockquote';
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
      if(isset($style['fontSize'])) {
        $css .= StylesHelper::setFontAndLineHeight(
          (int) $style['fontSize'],
          $selector
        );
        unset($style['fontSize']);
      }
      if(isset($style['fontFamily'])) {
        $css .= StylesHelper::setFontFamily(
          $style['fontFamily'],
          $selector
        );
        unset($style['fontFamily']);
      }
      $css .= StylesHelper::setStyle($style, $selector);
    }
    return $css;
  }

  function injectContentIntoTemplate($template, $data) {
    return preg_replace_callback('/{{\w+}}/', function ($matches) use (&$data) {
      return array_shift($data);
    }, $template);
  }

  function inlineCSSStyles($template) {
    return $this->CSS_inliner->inlineCSS(null, $template);
  }

  function renderTextVersion($template) {
    // TODO: add text rendering
    return $template;
  }

  function postProcessTemplate($template) {
    // replace all !important tags except for in the body tag
    $DOM = $this->DOM_parser->parseStr($template);
    $template = $DOM->query('.mailpoet_template');
    $template->html(
      str_replace('!important', '', $template->html())
    );
    // TODO: return array with html and text body
    return $DOM->__toString();
  }
}