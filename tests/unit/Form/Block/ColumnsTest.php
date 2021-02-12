<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Columns;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../HtmlParser.php';

class ColumnsTest extends \MailPoetUnitTest {
  /** @var Columns */
  private $columns;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'position' => '1',
    'type' => 'columns',
  ];

  public function _before() {
    parent::_before();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->columns = new Columns($wpMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderColumns() {
    $html = $this->columns->render($this->block, 'content');
    expect($html)->equals('<div class="mailpoet_form_columns mailpoet_paragraph">content</div>');
  }

  public function testItShouldRenderVerticalAlignClass() {
    $block = $this->block;
    $block['params']['vertical_alignment'] = 'top';
    $html = $this->columns->render($block, 'content');
    $column = $this->htmlParser->getElementByXpath($html, '//div[1]');
    $class = $this->htmlParser->getAttribute($column, 'class');
    expect($class->textContent)->stringContainsString('mailpoet_vertically_align_top');
  }

  public function testItShouldRenderCustomClass() {
    $block = $this->block;
    $block['params']['class_name'] = 'my-class';
    $html = $this->columns->render($block, 'content');
    $column = $this->htmlParser->getElementByXpath($html, '//div[1]');
    $class = $this->htmlParser->getAttribute($column, 'class');
    expect($class->textContent)->stringContainsString('my-class');
  }

  public function testItShouldRenderCustomBackground() {
    $block = $this->block;
    $block['params']['background_color'] = '#ffffff';
    $html = $this->columns->render($block, 'content');
    $columns = $this->htmlParser->getElementByXpath($html, '//div[1]');
    $style = $this->htmlParser->getAttribute($columns, 'style');
    expect($style->textContent)->stringContainsString('background-color:#ffffff;');
    $class = $this->htmlParser->getAttribute($columns, 'class');
    expect($class->textContent)->stringContainsString('mailpoet_column_with_background');
  }

  public function testItShouldRenderCustomTextColor() {
    $block = $this->block;
    $block['params']['text_color'] = '#ffffee';
    $html = $this->columns->render($block, 'content');
    $columns = $this->htmlParser->getElementByXpath($html, '//div[1]');
    $style = $this->htmlParser->getAttribute($columns, 'style');
    expect($style->textContent)->stringContainsString('color:#ffffee;');
  }

  public function testItShouldGradientBackground() {
    $block = $this->block;
    $block['params']['gradient'] = 'linear-gradient(red, yellow)';
    $html = $this->columns->render($block, 'content');
    $columns = $this->htmlParser->getElementByXpath($html, '//div[1]');
    $style = $this->htmlParser->getAttribute($columns, 'style');
    expect($style->textContent)->stringContainsString('background:linear-gradient(red, yellow);');
    $class = $this->htmlParser->getAttribute($columns, 'class');
    expect($class->textContent)->stringContainsString('mailpoet_column_with_background');
  }
}
