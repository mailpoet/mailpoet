<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Divider;

class DividerTest extends \MailPoetUnitTest {
  /** @var Divider */
  private $divider;

  public function _before() {
    parent::_before();
    $this->divider = new Divider();
  }

  public function testItShouldRenderDivider() {
    $html = $this->divider->render();
    expect($html)->equals('<hr class="mailpoet_divider" />');
  }
}
