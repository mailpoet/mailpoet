<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Divider;
use MailPoet\WP\Functions as WPFunctions;

class DividerTest extends \MailPoetUnitTest {
  /** @var Divider */
  private $divider;

  private $block = [
    'type' => 'divider',
    'name' => 'Divider',
    'id' => 'divider',
    'unique' => '1',
    'static' => '0',
    'params' => [],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->divider = new Divider($wpMock);
  }

  public function testItRendersOldDividerWithNoParams() {
    $result = $this->divider->render($this->block);
    expect($result)->stringContainsString('height: 1px;');
    expect($result)->stringContainsString("class='mailpoet_divider");
    expect($result)->stringContainsString("class='mailpoet_spacer");
    expect($result)->stringContainsString("border-top-style: solid;");
    expect($result)->stringContainsString("border-top-width: 1px;");
    expect($result)->stringContainsString("border-top-color: black;");
    expect($result)->stringContainsString("width: 100%");
  }

  public function testItRendersClassName() {
    $block = $this->block;
    $block['params']['class_name'] = 'abc';
    $result = $this->divider->render($block);
    expect($result)->stringContainsString("class='mailpoet_spacer abc");
  }

  public function testItRendersSpacer() {
    $block = $this->block;
    $block['params']['type'] = 'spacer';
    $block['params']['height'] = '10';
    $result = $this->divider->render($block);
    expect($result)->equals("<div class='mailpoet_spacer' style='height: 10px;'></div>");
  }

  public function testItRendersDivider() {
    $block = $this->block;
    $block['params']['type'] = 'divider';
    $block['params']['height'] = '12';
    $block['params']['style'] = 'dotted';
    $block['params']['divider_height'] = '10';
    $block['params']['divider_width'] = '50';
    $block['params']['color'] = 'red';

    $result = $this->divider->render($block);

    expect($result)->stringContainsString('height: 12px;');
    expect($result)->stringContainsString("class='mailpoet_divider");
    expect($result)->stringContainsString("class='mailpoet_spacer mailpoet_has_divider");
    expect($result)->stringContainsString("border-top-style: dotted;");
    expect($result)->stringContainsString("border-top-width: 10px;");
    expect($result)->stringContainsString("border-top-color: red;");
    expect($result)->stringContainsString("width: 50%");
  }
}
