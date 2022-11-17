<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Column;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;

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
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->columns = new Column($wpMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderColumn() {
    $html = $this->columns->render($this->block, 'content');
    expect($html)->equals('<div class="mailpoet_form_column">content</div>');
  }

  public function testItShouldRenderWidth() {
    $block = $this->block;
    $block['params']['width'] = '30px';
    $html = $this->columns->render($block, 'content');
    $column = $this->htmlParser->getElementByXpath($html, '//div[@class="mailpoet_form_column"]');
    $style = $this->htmlParser->getAttribute($column, 'style');
    expect($style->textContent)->stringContainsString('flex-basis:30px;');
  }

  public function testItShouldNotRenderWidthWhenOnlyUnitIsSet() {
    $block = $this->block;
    $block['params']['width'] = 'px';
    $html = $this->columns->render($block, 'content');
    $column = $this->htmlParser->getElementByXpath($html, '//div[@class="mailpoet_form_column"]');
    $this->assertInstanceOf(\DOMNamedNodeMap::class, $column->attributes);
    expect($column->attributes->getNamedItem('style'))->null();
  }

  public function testItShouldRenderWidthBackwardCompatible() {
    $block = $this->block;
    $block['params']['width'] = '30';
    $html = $this->columns->render($block, 'content');
    $column = $this->htmlParser->getElementByXpath($html, '//div[@class="mailpoet_form_column"]');
    $style = $this->htmlParser->getAttribute($column, 'style');
    expect($style->textContent)->stringContainsString('flex-basis:30%;');
  }

  public function testItShouldRenderPadding() {
    $block = $this->block;
    $block['params']['padding'] = ['top' => '10px', 'right' => '20px', 'bottom' => '30px', 'left' => '40px'];
    $html = $this->columns->render($block, 'content');
    $column = $this->htmlParser->getElementByXpath($html, '//div[@class="mailpoet_form_column"]');
    $style = $this->htmlParser->getAttribute($column, 'style');
    expect($style->textContent)->stringContainsString('padding:10px 20px 30px 40px;');
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
  }
}
