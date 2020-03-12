<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Divider;

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
    $this->divider = new Divider();
  }

  public function testItShouldRenderDivider() {
    $html = $this->divider->render($this->block);
    expect($html)->equals('<hr class="mailpoet_divider" />');
  }

  public function testItShouldRenderCustomClass() {
    $this->block['params']['class_name'] = 'my_class';
    $html = $this->divider->render($this->block);
    expect($html)->equals('<hr class="mailpoet_divider my_class" />');
  }
}
