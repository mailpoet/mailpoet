<?php declare(strict_types = 1);

namespace MailPoet\Test\Twig;

use Codeception\Util\Stub;
use MailPoet\Twig\Functions;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Twig\Environment;

class FunctionsTest extends \MailPoetTest {
  public function testItExecutesIsRtlFunction() {
    $template = ['template' => '{% if is_rtl() %}rtl{% endif %}'];
    $twig = new Environment(new \MailPoetVendor\Twig\Loader\ArrayLoader($template));
    WPFunctions::set(Stub::make(new WPFunctions, [
      'isRtl' => Stub::consecutive(true, false),
    ]));

    $twig->addExtension(new Functions());
    $resultRtl = $twig->render('template');
    expect($resultRtl)->equals('rtl');
    $resultNoRtl = $twig->render('template');
    expect($resultNoRtl)->isEmpty();
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
