<?php

namespace MailPoet\Test\Form\Util;

use MailPoet\Features\FeaturesController;
use MailPoet\Form\Util\Styles;

class StylesTest extends \MailPoetUnitTest {

  /** @var FeaturesController&\PHPUnit_Framework_MockObject_MockObject */
  private $featuresController;

  public function _before() {
    parent::_before();
    $this->featuresController = $this->createMock(FeaturesController::class);
    $this->featuresController
      ->expects($this->any())
      ->method('isSupported')
      ->willReturn(false);
  }

  public function testItSetsDefaultCSSStyles() {
    $styles = new Styles($this->featuresController);
    expect($styles->getDefaultStyles())->notEmpty();
  }

  public function testItProcessesAndRendersStyles() {
    $stylesheet = '
    /* some comment */
    input[name=first_name]    , input.some_class,     .some_class { color: red  ; background: blue; } .another_style { fonT-siZe: 20px                            }
    ';
    $styleProcesser = new Styles($this->featuresController);
    $extractedAndPrefixedStyles = $styleProcesser->render($stylesheet, $prefix = 'mailpoet');
    // 1. comments should be stripped
    // 2. each selector should be refixed
    // 3. multiple spaces, missing semicolons should be fixed
    // 4. each style should be on a separate line
    $expectedResult = "mailpoet input[name=first_name], mailpoet input.some_class, mailpoet .some_class { color: red; background: blue; }" . PHP_EOL
    . "mailpoet .another_style { font-size: 20px; }";
    expect($extractedAndPrefixedStyles)->equals($expectedResult);
  }
}
