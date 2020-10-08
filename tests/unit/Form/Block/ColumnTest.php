<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Column;
use MailPoet\Test\Form\HtmlParser;

require_once __DIR__ . '/../HtmlParser.php';

class ColumnTest extends \MailPoetUnitTest {
  /** @var Column */
  private $columns;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'position' => '1',
    'type' => 'column',
    'params' => [],
  ];

  public function _before() {
    parent::_before();
    $this->columns = new Column();
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderColumn() {
    $html = $this->columns->render($this->block, 'content');
    expect($html)->equals('<div class="mailpoet_form_column">content</div>');
  }

  public function testItShouldRenderWidth() {
    $block = $this->block;
    $block['params']['width'] = 30;
    $html = $this->columns->render($block, 'content');
    $column = $this->htmlParser->getElementByXpath($html, '//div[@class="mailpoet_form_column"]');
    $style = $this->htmlParser->getAttribute($column, 'style');
    expect($style->textContent)->stringContainsString('flex-basis:30%;');
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
}
