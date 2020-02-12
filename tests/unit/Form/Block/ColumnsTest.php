<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Columns;

class ColumnsTest extends \MailPoetUnitTest {
  /** @var Columns */
  private $columns;

  private $block = [
    'position' => '1',
    'type' => 'columns',
  ];

  public function _before() {
    parent::_before();
    $this->columns = new Columns();
  }

  public function testItShouldRenderColumns() {
    $html = $this->columns->render($this->block, 'content');
    expect($html)->equals('<div class="mailpoet_form_columns">content</div>');
  }
}
