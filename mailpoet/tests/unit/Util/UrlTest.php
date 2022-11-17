<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use Codeception\Stub;
use MailPoet\Util\Url;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetUnitTest {
  public function testCurrentUrlReturnsHomeUrlOnHome() {
    $homeUrl = 'http://example.com';
    $urlHelper = new Url(Stub::make(new WPFunctions(), [
      'homeUrl' => $homeUrl,
      'addQueryArg' => '',
    ]));
    $currentUrl = $urlHelper->getCurrentUrl();
    expect($currentUrl)->equals($homeUrl);
  }
}
