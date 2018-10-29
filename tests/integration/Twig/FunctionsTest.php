<?php

namespace MailPoet\Test\Twig;

use AspectMock\Test as Mock;
use Carbon\Carbon;
use MailPoet\Models\Setting;
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

  function testInstalledInLastTwoWeeksFunction() {
    $template = array('template' => '{% if mailpoet_installed_in_last_two_weeks() %}last_two_weeks{% endif %}');
    $twig = new \Twig_Environment(new \Twig_Loader_Array($template));
    $twig->addExtension(new Functions());

    Setting::setValue('installed_at', Carbon::now());
    $result = $twig->render('template');
    expect($result)->equals('last_two_weeks');

    Setting::setValue('installed_at', Carbon::now()->subDays(13));
    $result = $twig->render('template');
    expect($result)->equals('last_two_weeks');

    Setting::setValue('installed_at', Carbon::now()->subDays(14));
    $result = $twig->render('template');
    expect($result)->isEmpty();
  }
}