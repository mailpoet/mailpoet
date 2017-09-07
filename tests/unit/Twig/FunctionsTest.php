<?php

namespace MailPoet\Test\Twig;

use AspectMock\Test as Mock;
use MailPoet\Twig\Functions;

class FunctionsTest extends \MailPoetTest {
  function testItExecutesIsRtlFunction() {
    $template = array('template' => '{% if is_rtl() %}rtl{% endif %}');
    $twig = new \Twig_Environment(new \Twig_Loader_Array($template));
    $twig->addExtension(new Functions());

    Mock::func('MailPoet\Twig', 'is_rtl', true);
    $result = $twig->render('template');
    expect($result)->equals('rtl');

    Mock::func('MailPoet\Twig', 'is_rtl', false);
    $result = $twig->render('template');
    expect($result)->isEmpty();
  }
}