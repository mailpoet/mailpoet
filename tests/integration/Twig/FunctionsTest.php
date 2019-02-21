<?php

namespace MailPoet\Test\Twig;

use Codeception\Util\Stub;
use MailPoet\Twig\Functions;
use MailPoet\WP\Functions as WPFunctions;

class FunctionsTest extends \MailPoetTest {
  function testItExecutesIsRtlFunction() {
    $template = array('template' => '{% if is_rtl() %}rtl{% endif %}');
    $twig = new \Twig_Environment(new \Twig_Loader_Array($template));
    $twig->addExtension(new Functions());

    WPFunctions::set(Stub::make(new WPFunctions, [
      'isRtl' => true
    ]));
    $result = $twig->render('template');
    expect($result)->equals('rtl');

    WPFunctions::set(Stub::make(new WPFunctions, [
      'isRtl' => false
    ]));
    $result = $twig->render('template');
    expect($result)->isEmpty();
  }

  function _after() {
    WPFunctions::set(new WPFunctions);
  }
}
