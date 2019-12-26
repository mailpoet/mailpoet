<?php

namespace MailPoet\Test\Twig;

use Codeception\Util\Stub;
use MailPoet\Twig\Functions;
use MailPoet\WP\Functions as WPFunctions;

class FunctionsTest extends \MailPoetTest {
  public function testItExecutesIsRtlFunction() {
    $template = ['template' => '{% if is_rtl() %}rtl{% endif %}'];
    $twig = new \MailPoetVendor\Twig_Environment(new \MailPoetVendor\Twig_Loader_Array($template));
    WPFunctions::set(Stub::make(new WPFunctions, [
      'isRtl' => Stub::consecutive(true, false),
    ]));

    $twig->addExtension(new Functions());
    $result_rtl = $twig->render('template');
    expect($result_rtl)->equals('rtl');
    $result_no_rtl = $twig->render('template');
    expect($result_no_rtl)->isEmpty();
  }

  public function _after() {
    WPFunctions::set(new WPFunctions);
  }
}
