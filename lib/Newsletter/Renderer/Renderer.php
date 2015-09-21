<?php
namespace MailPoet\Newsletter\Renderer;
if(!defined('ABSPATH')) exit;

class Renderer {

  public $template = 'Template.html';

  function __construct($newsletterData) {
    $this->blocksRenderer = new Blocks\Renderer();
    $this->columnsRenderer = new Columns\Renderer();
    $this->stylesHelper = new StylesHelper();
    $this->DOMQuery = new \pQuery();
    $this->CSSInliner = new \MailPoet\Util\CSS();
    $this->data = $newsletterData;
    $this->template = file_get_contents(dirname(__FILE__) . '/' . $this->template);
  }

  function renderAll() {
    $newsletterContent = $this->renderContent($this->data['data']);
    $newsletterStyles = $this->renderStyles($this->data['styles']);

    $renderedTemplate = $this->renderTemplate($this->template, array(
      $newsletterStyles,
      $newsletterContent
    ));
    $renderedTemplateWithInlinedStyles = $this->inlineCSSStyles($renderedTemplate);

    return $this->postProcessRenderedTemplate($renderedTemplateWithInlinedStyles);
  }

  function renderContent($content) {
    array_map(function ($contentBlock) use (&$newsletterContent) {
      $columnCount = count($contentBlock['blocks']);
      $columnData = $this->blocksRenderer->render($contentBlock);
      $newsletterContent .= $this->columnsRenderer->render($columnCount, $columnData);
    }, $content['blocks']);
    return $newsletterContent;
  }

  function renderStyles($styles) {
    $newsletterStyles = '';
    foreach ($styles as $selector => $style) {
      switch ($selector) {
      case 'text':
        $selector = 'span.paragraph, ul, ol';
      break;
      case 'background':
        $selector = '.mailpoet_content-wrapper';
      break;
      case 'link':
        $selector = '.mailpoet_content-wrapper a';
      break;
      case 'newsletter':
        $selector = '.mailpoet_container, .mailpoet_col-one, .mailpoet_col-two, .mailpoet_col-three';
      break;
      }
      $newsletterStyles .= $selector . '{' . PHP_EOL;
      foreach ($style as $attribute => $individualStyle) {
        $newsletterStyles .= $this->stylesHelper->translateCSSAttribute($attribute) . ':' . $individualStyle . ';' . PHP_EOL;
      }
      $newsletterStyles .= '}' . PHP_EOL;
    }

    return $newsletterStyles;
  }

  function renderTemplate($template, $data) {
    return preg_replace_callback('/{{\w+}}/', function ($matches) use (&$data) {
      return array_shift($data);
    }, $template);
  }

  function inlineCSSStyles($template) {
    return $this->CSSInliner->inlineCSS(null, $template);
  }

  function postProcessRenderedTemplate($template) {
    // remove padding from last element inside each column
    $DOM = $this->DOMQuery->parseStr($template);
    $lastColumnElement = $DOM->query('.mailpoet_col > tbody > tr:last-child > td');
    foreach ($lastColumnElement as $element) {
      $element->setAttribute('style', str_replace('padding-bottom:20px;', '', $element->attributes['style']));
    }

    return $DOM->__toString();
  }
}