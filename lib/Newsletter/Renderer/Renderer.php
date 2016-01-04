<?php
namespace MailPoet\Newsletter\Renderer;

if (!defined('ABSPATH')) exit;

class Renderer {
  public $template = 'Template.html';

  function __construct($newsletterData) {
    $this->blocksRenderer = new Blocks\Renderer();
    $this->columnsRenderer = new Columns\Renderer();
    $this->DOMQuery = new \pQuery();
    $this->CSSInliner = new \MailPoet\Util\CSS();
    $this->data = $newsletterData;
    $this->template = file_get_contents(dirname(__FILE__) . '/' . $this->template);
  }

  function render() {
    $newsletterContent = $this->renderContent($this->data['content']);
    $newsletterStyles = $this->renderGlobalStyles($this->data['globalStyles']);
    $newsletterTitle = '';
    $newsletterPreheader = '';
    $renderedTemplate = $this->renderTemplate($this->template, array(
      $newsletterTitle,
      $newsletterStyles,
      $newsletterPreheader,
      $newsletterContent
    ));
    $renderedTemplateWithInlinedStyles = $this->inlineCSSStyles($renderedTemplate);
    return $this->postProcessTemplate($renderedTemplateWithInlinedStyles);
  }

  function renderContent($content) {
    $content = array_map(function ($contentBlock) {
      $columnCount = count($contentBlock['blocks']);
      $columnData = $this->blocksRenderer->render($contentBlock, $columnCount);
      return $this->columnsRenderer->render(
        $contentBlock['styles'],
        $columnCount,
        $columnData
      );
    }, $content['blocks']);
    return implode('', $content);
  }

  function renderGlobalStyles($styles) {
    $css = '';
    foreach ($styles as $selector => $style) {
      switch ($selector) {
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
        $selector = 'body, .mailpoet_content-wrapper';
      break;
      case 'link':
        $selector = '.mailpoet_content-wrapper a';
      break;
      case 'wrapper':
        $selector = '.mailpoet_content';
      break;
      }
      if (isset($style['fontSize'])) {
        $css .= StylesHelper::setFontAndLineHeight(
          (int) $style['fontSize'],
          $selector
        );
        unset($style['fontSize']);
      }
      if (isset($style['fontFamily'])) {
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

  function renderTemplate($template, $data) {
    return preg_replace_callback('/{{\w+}}/', function ($matches) use (&$data) {
      return array_shift($data);
    }, $template);
  }

  function inlineCSSStyles($template) {
    return $this->CSSInliner->inlineCSS(null, $template);
  }

  function postProcessTemplate($template) {
    // replace all !important tags except for in the body tag
    $DOM = $this->DOMQuery->parseStr($template);
    $lastColumnElement = $DOM->query('.mailpoet_template');
    $lastColumnElement->html(str_replace('!important', '', $lastColumnElement->html()));
    return $DOM->__toString();
  }
}