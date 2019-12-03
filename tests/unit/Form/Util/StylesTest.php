<?php

namespace MailPoet\Test\Form\Util;

use MailPoet\Features\FeaturesController;
use MailPoet\Form\Util\Styles;
use PHPUnit\Framework\MockObject\MockObject;

class StylesTest extends \MailPoetUnitTest {

  /** @var FeaturesController&MockObject */
  private $features_controller;

  function _before() {
    parent::_before();
    $this->features_controller = $this->createMock(FeaturesController::class);
    $this->features_controller
      ->expects($this->any())
      ->method('isSupported')
      ->willReturn(false);
  }

  function testItSetsDefaultCSSStyles() {
    $styles = new Styles($this->features_controller);
    expect($styles->getDefaultStyles())->notEmpty();
  }

  function testItProcessesAndRendersStyles() {
    $stylesheet = '
    /* some comment */
    input[name=first_name]    , input.some_class,     .some_class { color: red  ; background: blue; } .another_style { fonT-siZe: 20px                            }
    ';
    $style_processer = new Styles($this->features_controller);
    $extracted_and_prefixed_styles = $style_processer->render($stylesheet, $prefix = 'mailpoet');
    // 1. comments should be stripped
    // 2. each selector should be refixed
    // 3. multiple spaces, missing semicolons should be fixed
    // 4. each style should be on a separate line
    $expected_result = "mailpoet input[name=first_name], mailpoet input.some_class, mailpoet .some_class { color: red; background: blue; }" . PHP_EOL
    . "mailpoet .another_style { font-size: 20px; }";
    expect($extracted_and_prefixed_styles)->equals($expected_result);
  }
}
