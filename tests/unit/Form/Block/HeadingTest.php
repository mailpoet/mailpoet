<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Heading;

class HeadingTest extends \MailPoetUnitTest {
  /** @var Heading */
  private $heading;

  public function _before() {
    parent::_before();
    $this->heading = new Heading();
  }

  public function testItShouldRenderHeading() {
    $html = $this->heading->render([], []);
    expect($html)->startsWith('<h2');
  }
}
