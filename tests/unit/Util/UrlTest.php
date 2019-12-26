<?php

namespace MailPoet\Test\Util;

use Codeception\Stub;
use MailPoet\Util\Url;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetUnitTest {
  public function testCurrentUrlReturnsHomeUrlOnHome() {
    $home_url = 'http://example.com';
    $url_helper = new Url(Stub::make(new WPFunctions(), [
      'homeUrl' => $home_url,
      'addQueryArg' => '',
    ]));
    $current_url = $url_helper->getCurrentUrl();
    expect($current_url)->equals($home_url);
  }
}