<?php

use MailPoet\Form\Util\Styles;

class StylesTest extends MailPoetTest {
  function testItSetsDefaultCSSStyles() {
    expect(property_exists('MailPoet\Form\Util\Styles', 'default_styles'))->true();
    expect(Styles::$default_styles)->notEmpty();
  }

  function testItProcessesAndRendersStyles() {
    $stylesheet = '
    /* some comment */
    input[name=first_name]    , input.some_class,     .some_class { color: red  ; background: blue; } .another_style { fonT-siZe: 20px                            }
    ';
    $style_processer = new Styles($stylesheet);
    $extracted_and_prefixed_styles = $style_processer->render($prefix = 'mailpoet');
    // 1. comments should be stripped
    // 2. each selector should be refixed
    // 3. multiple spaces, missing semicolons should be fixed
    // 4. each style should be on a separate line
    $expected_result = <<<EOL
mailpoet input[name=first_name], mailpoet input.some_class, mailpoet .some_class { color: red; background: blue; }
mailpoet .another_style { font-size: 20px; }
EOL;
    expect($extracted_and_prefixed_styles)->equals($expected_result);
  }
}